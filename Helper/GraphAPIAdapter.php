<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Helper;

use CURLFile;
use Facebook\BusinessExtension\Model\FacebookOrder;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Psr\Log\LoggerInterface;

class GraphAPIAdapter
{
    const GET_ORDERS_LIMIT = 25;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var mixed
     */
    private $accessToken;

    /**
     * @var string
     */
    private $graphAPIVersion = '12.0';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $debugMode = false;

    public function __construct(SystemConfig $systemConfig, LoggerInterface $logger)
    {
        $this->systemConfig = $systemConfig;
        $this->logger = $logger;
        $this->accessToken = $systemConfig->getAccessToken();
        $this->client = new Client([
            'base_uri' => "https://graph.facebook.com/v{$this->graphAPIVersion}/",
            'timeout' => 60,
        ]);
        $this->debugMode = $systemConfig->isDebugMode();
    }

    /**
     * @param $accessToken
     * @return $this
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * @param $debugMode
     * @return $this
     */
    public function setDebugMode($debugMode)
    {
        $this->debugMode = $debugMode;
        return $this;
    }

    /**
     * @return string
     */
    private function getUniqId()
    {
        return uniqid();
    }

    /**
     * @param $method
     * @param $endpoint
     * @param $request
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @todo implement custom logger class, remove access token from logs
     */
    protected function callApi($method, $endpoint, $request)
    {
        try {
            if ($this->debugMode) {
                $this->logger->debug(print_r([
                    'endpoint' => "/{$method} {$endpoint}",
                    'request' => $request,
                ], true));
            }
            $response = $this->client->request($method, $endpoint, ['query' => $request]);
            if ($this->debugMode) {
                $this->logger->debug(print_r([
                    'response' => [
                        'status_code' => $response->getStatusCode(),
                        'reason_phrase' => $response->getReasonPhrase(),
                        'headers' => json_encode(array_map(function ($a) {
                            return $a[0];
                        }, $response->getHeaders())),
                        'body' => (string)$response->getBody(),
                    ]
                ], true));
            }
            return $response;
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $this->logger->debug($e->getMessage());
            if (stripos($e->getMessage(), 'truncated') !== 'false') {
                $this->logger->debug('Full error: ' . (string)$response->getBody());
            }
            throw $e;
        }
    }

    /**
     * @param $userToken
     * @return false|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPageTokenFromUserToken($userToken)
    {
        $request = [
            'access_token' => $userToken
        ];
        $response = $this->callApi('GET', 'me/accounts', $request);
        $response = json_decode($response->getBody(), true);
        return $response['data'][0]['access_token'] ?? false;
    }

    /**
     * @param null|string $accessToken
     * @return false|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPageMerchantSettingsId($accessToken = null)
    {
        $request = [
            'access_token' => $accessToken ?: $this->accessToken,
            'fields' => 'commerce_merchant_settings',
        ];
        $response = $this->callApi('GET', 'me', $request);
        $response = json_decode($response->getBody(), true);
        return $response['commerce_merchant_settings']['data'][0]['id'] ?? false;
    }

    /**
     * @param $commerceAccountId
     * @param null $accessToken
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @todo check store setup status
     */
    public function getCommerceAccountData($commerceAccountId, $accessToken = null)
    {
        $request = [
            'access_token' => $accessToken ?: $this->accessToken,
            'fields' => 'merchant_page,product_catalogs',
        ];
        $response = $this->callApi('GET', "{$commerceAccountId}", $request);
        $response = json_decode($response->getBody(), true);
        return ['page_id' => $response['merchant_page']['id'], 'catalog_id' => $response['product_catalogs']['data'][0]['id']];
    }

    /**
     * @param $commerceAccountId
     * @param null $accessToken
     * @return array|mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function associateMerchantSettingsWithApp($commerceAccountId, $accessToken = null)
    {
        $request = [
            'access_token' => $accessToken ?: $this->accessToken,
        ];
        $response = $this->callApi('POST', "{$commerceAccountId}/order_management_apps", $request);
        // @todo check for success:true upstream
        $response = json_decode($response->getBody(), true);
        return $response;
    }

    public function getCatalogFeeds($catalogId)
    {

        $requestFields = [
            'id',
            'file_name',
            'name',
            'catalog_item_type',
        ];


        $response = $this->callApi('GET', "{$catalogId}/product_feeds", [
            'access_token' => $this->accessToken,
            'fields' => implode(',', $requestFields),
        ]);
        $response = json_decode($response->getBody(), true);
        return $response['data'];
    }

    public function getOfferFeeds($catalogId)
    {
        $catalogFeeds = $this->getCatalogFeeds($catalogId);
        return array_filter($catalogFeeds, function ($row) {
            return $row['catalog_item_type'] === 'OFFER_ITEM';
        });
    }

    public function getFeed($feedId)
    {
        $response = $this->callApi('GET', "{$feedId}", [
            'access_token' => $this->accessToken,
        ]);
        $response = json_decode($response->getBody(), true);
        return $response;
    }

    public function createEmptyFeed($catalogId, $name, $isPromotion = false)
    {
        $request =
            [
                'access_token' => $this->accessToken,
                'name' => $name,
            ];
        if ($isPromotion) {
            $request['feed_type'] = 'OFFER';
        }
        $response = $this->callApi('POST', "{$catalogId}/product_feeds", $request);
        $response = json_decode($response->getBody(), true);
        return $response['id'];
    }


    /**
     * @param $feedId
     * @param $feed
     * @return mixed
     */
    public function pushProductFeed($feedId, $feed)
    {
        return $this->pushFeed($feedId, $feed);
    }

    /**
     * @param $feedId
     * @param $feed
     * @return mixed
     */
    public function pushFeed($feedId, $feed)
    {
        $endpoint = "https://graph.facebook.com/v{$this->graphAPIVersion}/$feedId/uploads";

        $ch = curl_init($endpoint);

        $file = new CURLFile($feed, mime_content_type($feed), basename($feed));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $file, 'access_token' => $this->accessToken]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $result = curl_error($ch);
        }
        curl_close($ch);

        if ($this->debugMode) {
            $this->logger->debug(print_r([
                'endpoint' => "POST {$endpoint}",
                'file' => $feed,
                'response' => $result
            ], true));
        }

        return json_decode($result);
    }

    public function catalogBatchRequest($catalogId, $requests)
    {
        $response = $this->callApi('POST', "{$catalogId}/items_batch", [
            'access_token' => $this->accessToken,
            'requests' => json_encode($requests),
            'item_type' => 'PRODUCT_ITEM'
        ]);
        $response = json_decode($response->getBody(), true);
        return $response;
    }

    /**
     * @param $pageId
     * @param false|string $cursorAfter
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOrders($pageId, $cursorAfter = false)
    {
        $requestFields = [
            'id',
            'buyer_details',
            'channel',
            'created',
            'estimated_payment_details',
            'ship_by_date',
            'order_status',
            'selected_shipping_option',
            'shipping_address{first_name, last_name, street1, street2, city, postal_code, country}',
            'payments',
            'promotion_details{applied_amount, coupon_code, target_granularity, sponsor}',
            'last_updated',
        ];
        $request = [
            'access_token' => $this->accessToken,
            'state' => FacebookOrder::STATE_CREATED,
            'fields' => implode(',', $requestFields),
            'limit' => self::GET_ORDERS_LIMIT,
        ];
        if ($cursorAfter) {
            $request['after'] = $cursorAfter;
        }
        $response = $this->callApi('GET', "{$pageId}/commerce_orders", $request);
        return json_decode($response->getBody(), true);
    }

    /**
     * @param $fbOrderId
     * @return array|mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOrderItems($fbOrderId)
    {
        $requestFields = [
            'retailer_id',
            'quantity',
            'price_per_unit',
            'is_price_per_unit_tax_inclusive',
            'tax_details',
            'product_id'
        ];
        $request = [
            'access_token' => $this->accessToken,
            'fields' => implode(',', $requestFields),
        ];
        $response = $this->callApi('GET', "{$fbOrderId}/items", $request);
        return json_decode($response->getBody(), true);
    }

    /**
     * @param $pageId
     * @param array $orderIds
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function acknowledgeOrders($pageId, array $orderIds)
    {
        $request = [];
        foreach ($orderIds as $magentoOrderId => $fbOrderId) {
            $request[] = ['id' => $fbOrderId, 'merchant_order_reference' => $magentoOrderId];
        }
        $response = $this->callApi('POST', "{$pageId}/acknowledge_orders", [
            'access_token' => $this->accessToken,
            'idempotency_key' => $this->getUniqId(),
            'orders' => json_encode($request),
        ]);
        return json_decode($response->getBody(), true);
    }

    /**
     * @param $fbOrderId
     * @param $items
     * @param $trackingInfo
     * @param $fulfillmentAddressData
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function markOrderAsShipped($fbOrderId, $items, $trackingInfo, $fulfillmentAddressData)
    {
        $request = ['access_token' => $this->accessToken,
            'idempotency_key' => $this->getUniqId(),
            'items' => json_encode($items),
            'tracking_info' => json_encode($trackingInfo),];
        if ($fulfillmentAddressData) {
            $request['should_use_default_fulfillment_location'] = false;
            $request['fulfillment']['fulfillment_address'] = $fulfillmentAddressData;
        } else {
            $request['should_use_default_fulfillment_location'] = true;
        }
        $response = $this->callApi('POST', "{$fbOrderId}/shipments", $request);
        $response = json_decode($response->getBody(), true);
        return $response;
    }

    /**
     * @param $fbOrderId
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cancelOrder($fbOrderId)
    {
        // Magento doesn't support admin providing reason code or description for order cancellation
        $cancelReason = [
            'reason_code' => 'CUSTOMER_REQUESTED',
            'reason_description' => 'Cancelled from Magento',
        ];
        $response = $this->callApi('POST', "{$fbOrderId}/cancellations", [
            'access_token' => $this->accessToken,
            'idempotency_key' => $this->getUniqId(),
            'cancel_reason' => $cancelReason,
            'restock_items' => true,
        ]);
        $response = json_decode($response->getBody(), true);
        return $response;
    }

    /**
     * @param $fbOrderId
     * @param $items
     * @param $shippingRefundAmount
     * @param string $currency Order's currency code. Examples: "USD", "GBP"
     * @param null $reasonText
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refundOrder($fbOrderId, $items, $shippingRefundAmount, $currency, $reasonText = null)
    {
        $request = [
            'access_token' => $this->accessToken,
            'idempotency_key' => $this->getUniqId(),
            'reason_code' => 'REFUND_REASON_OTHER',
            'reason_text' => $reasonText,
            'items' => json_encode($items),
            'shipping' => json_encode(['shipping_refund' => ['amount' => $shippingRefundAmount, 'currency' => $currency]]),
        ];
        if ($reasonText) {
            $request['reason_text'] = $reasonText;
        }

        $response = $this->callApi('POST', "{$fbOrderId}/refunds", $request);
        $response = json_decode($response->getBody(), true);
        return $response;
    }

    /**
     * @param $productId
     * @return array|mixed|object
     */
    public function getProductInfo($productId)
    {
        $requestFields = [
            'price'
        ];

        $request = [
            'access_token' => $this->accessToken,
            'fields' => implode(',', $requestFields),
        ];
        $response = $this->callApi('GET', "{$productId}", $request);
        return json_decode($response->getBody(), true);
    }
}
