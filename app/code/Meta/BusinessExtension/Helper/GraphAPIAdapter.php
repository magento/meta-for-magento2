<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Meta\BusinessExtension\Helper;

use CURLFile;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\View\FileFactory;

class GraphAPIAdapter
{
    private const ORDER_STATE_CREATED = 'CREATED';
    private const GET_ORDERS_LIMIT = 25;

    /**
     * @var mixed
     */
    private $accessToken;

    /**
     * @var string
     */
    private $graphAPIVersion = '15.0';

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

    /**
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * GraphAPIAdapter constructor.
     *
     * @param SystemConfig $systemConfig
     * @param LoggerInterface $logger
     * @param CurlFactory $curlFactory
     * @param FileFactory $fileFactory
     */
    public function __construct(
        SystemConfig $systemConfig,
        LoggerInterface $logger,
        CurlFactory $curlFactory,
        FileFactory $fileFactory
    ) {
        $this->logger = $logger;
        $this->accessToken = $systemConfig->getAccessToken();
        $this->client = new Client([
            'base_uri' => "https://graph.facebook.com/v{$this->graphAPIVersion}/",
            'timeout' => 60,
        ]);
        $this->debugMode = $systemConfig->isDebugMode();
        $this->curlFactory = $curlFactory;
        $this->fileFactory = $fileFactory;
    }

    /**
     * Set access token
     *
     * @param null|string $accessToken
     * @return $this
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * Set debug mode
     *
     * @param bool $debugMode
     * @return $this
     */
    public function setDebugMode($debugMode)
    {
        $this->debugMode = $debugMode;
        return $this;
    }

    /**
     * Get uniq Id
     *
     * @return string
     */
    private function getUniqId()
    {
        return uniqid();
    }

    /**
     * Call api
     *
     * @param string $method
     * @param string $endpoint
     * @param array $request
     * @return \Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
     * @todo implement custom logger class, remove access token from logs
     */
    private function callApi($method, $endpoint, $request)
    {
        try {
            if ($this->debugMode) {
                $this->logger->debug(json_encode([
                    'endpoint' => "/{$method} {$endpoint}",
                    'request' => $request,
                ], JSON_PRETTY_PRINT));
            }
//            TODO: repalce with admin user local
            $request['locale'] = 'en_US';
            $response = $this->client->request($method, $endpoint, ['query' => $request]);
            if ($this->debugMode) {
                $this->logger->debug(json_encode([
                    'response' => [
                        'status_code' => $response->getStatusCode(),
                        'reason_phrase' => $response->getReasonPhrase(),
                        'headers' => json_encode(array_map(function ($a) {
                            return $a[0];
                        }, $response->getHeaders())),
                        'body' => (string)$response->getBody(),
                    ]
                ], JSON_PRETTY_PRINT));
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
     * Get page token from user token
     *
     * @param null|string $userToken
     * @return false|string
     * @throws GuzzleException
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
     * Get page Id from user token
     *
     * @param null|string $userToken
     * @return false|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPageIdFromUserToken($userToken)
    {
        $request = [
            'access_token' => $userToken
        ];
        $response = $this->callApi('GET', 'me/accounts', $request);
        $response = json_decode($response->getBody(), true);
        return $response['data'][0]['id'] ?? false;
    }

    /**
     * Get page access token
     *
     * @param null|string $accessToken
     * @param null|string $pageId
     * @return false|string
     * @throws GuzzleException
     */
    public function getPageAccessToken($accessToken, $pageId)
    {
        $request = [
            'access_token' => $accessToken,
            'fields' => 'access_token'
        ];
        $response = $this->callApi('GET', $pageId, $request);
        $response = json_decode($response->getBody(), true);
        return $response['access_token'] ?? false;
    }

    /**
     * Get page merchant settings Id
     *
     * @param null|string $accessToken
     * @param null|string $pageId
     * @return false|string
     * @throws GuzzleException
     */
    public function getPageMerchantSettingsId($accessToken = null, $pageId = null)
    {
        $request = [
            'access_token' => $accessToken ?: $this->accessToken,
            'fields' => 'commerce_merchant_settings',
        ];
        $response = $this->callApi('GET', $pageId ?? 'me', $request);
        $response = json_decode($response->getBody(), true);
        return $response['commerce_merchant_settings']['data'][0]['id'] ?? false;
    }

    /**
     * Get commerce account data
     *
     * @param mixed $commerceAccountId
     * @param mixed|null $accessToken
     * @return array
     * @throws GuzzleException
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
        return [
            'page_id' => $response['merchant_page']['id'],
            'catalog_id' => $response['product_catalogs']['data'][0]['id']
        ];
    }

    /**
     * Associate merchant settings with app
     *
     * @param mixed|null $commerceAccountId
     * @param mixed|null $accessToken
     * @return array|mixed|\Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
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

    /**
     * Get catalog feeds
     *
     * @param mixed $catalogId
     * @return mixed
     * @throws GuzzleException
     */
    public function getCatalogFeeds($catalogId)
    {
        $requestFields = [
            'id',
            'file_name',
            'name'
        ];

        $response = $this->callApi('GET', "{$catalogId}/product_feeds", [
            'access_token' => $this->accessToken,
            'fields' => implode(',', $requestFields),
        ]);
        $response = json_decode($response->getBody(), true);
        return $response['data'];
    }

    /**
     * Get feed
     *
     * @param string $feedId
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
     */
    public function getFeed($feedId)
    {
        $response = $this->callApi('GET', "{$feedId}", [
            'access_token' => $this->accessToken,
        ]);
        $response = json_decode($response->getBody(), true);
        return $response;
    }

    /**
     * Create empty feed
     *
     * @param mixed $catalogId
     * @param string $name
     * @param bool $isPromotion
     * @return mixed
     * @throws GuzzleException
     */
    public function createEmptyFeed($catalogId, $name, $isPromotion = false)
    {
        $request = [
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
     * Push product feed
     *
     * @param string $feedId
     * @param string $feed
     * @return mixed
     */
    public function pushProductFeed($feedId, $feed)
    {
        return $this->pushFeed($feedId, $feed);
    }

    /**
     * Push feed
     *
     * @param string $feedId
     * @param string $feed
     * @return mixed
     */
    public function pushFeed($feedId, $feed)
    {
        $endpoint = "https://graph.facebook.com/v{$this->graphAPIVersion}/$feedId/uploads";
        try {
            $curl = $this->curlFactory->create();
            $fileBaseName = $this->fileFactory->create(['filename' => $feed, 'module' => ''])->getName();

            $file = new CURLFile($feed, mime_content_type($feed), $fileBaseName);
            $curl->setOptions([ // This will override the $params to the post function
                CURLOPT_POSTFIELDS => ['file' => $file, 'access_token' => $this->accessToken]
            ]);
            $curl->post($endpoint, ['access_token' => '']); // Gets overridden, but still needs 1 param
            $result = $curl->getBody();
        } catch (\Exception $e) {
            $result = $e->getMessage();
        }

        if ($this->debugMode) {
            $this->logger->debug(json_encode([
                'endpoint' => "POST {$endpoint}",
                'file' => $feed,
                'response' => $result
            ], JSON_PRETTY_PRINT));
        }

        return json_decode($result);
    }

    /**
     * Catalog batch request
     *
     * @param mixed $catalogId
     * @param array $requests
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
     */
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
     * Get orders
     *
     * @param mixed $pageId
     * @param false|string $cursorAfter
     * @return array
     * @throws GuzzleException
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
            'promotion_details{applied_amount, coupon_code, target_granularity, sponsor, campaign_name}',
            'last_updated',
        ];
        $request = [
            'access_token' => $this->accessToken,
            'state' => self::ORDER_STATE_CREATED,
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
     * Get order items
     *
     * @param mixed $fbOrderId
     * @return array|mixed|\Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
     */
    public function getOrderItems($fbOrderId)
    {
        $requestFields = [
            'retailer_id',
            'quantity',
            'price_per_unit',
            'tax_details',
            'product_id',
            'promotion_details'
        ];
        $request = [
            'access_token' => $this->accessToken,
            'fields' => implode(',', $requestFields),
        ];
        $response = $this->callApi('GET', "{$fbOrderId}/items", $request);
        return json_decode($response->getBody(), true);
    }

    /**
     * Acknowledge orders
     *
     * @param mixed $pageId
     * @param array $orderIds
     * @return mixed
     * @throws GuzzleException
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
     * Mark order as shipped
     *
     * @param mixed $fbOrderId
     * @param array $items
     * @param array $trackingInfo
     * @param array $fulfillmentAddressData
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
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
     * Cancel order
     *
     * @param mixed $fbOrderId
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
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
     * Refund order
     *
     * @param mixed $fbOrderId
     * @param array $items
     * @param float|null $shippingRefundAmount
     * @param string $currency Order's currency code. Examples: "USD", "GBP"
     * @param null|string $reasonText
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
     */
    public function refundOrder($fbOrderId, $items, $shippingRefundAmount, $currency, $reasonText = null)
    {
        $request = [
            'access_token' => $this->accessToken,
            'idempotency_key' => $this->getUniqId(),
            'reason_code' => 'REFUND_REASON_OTHER',
            'reason_text' => $reasonText,
            'items' => json_encode($items),
            'shipping' => json_encode([
                'shipping_refund' => [
                    'amount' => $shippingRefundAmount,
                    'currency' => $currency
                ]
            ]),
        ];
        if ($reasonText) {
            $request['reason_text'] = $reasonText;
        }

        $response = $this->callApi('POST', "{$fbOrderId}/refunds", $request);
        $response = json_decode($response->getBody(), true);
        return $response;
    }

    /**
     * Get product info
     *
     * @param mixed $fbProductId
     * @return array|mixed|object
     * @throws GuzzleException
     */
    public function getProductInfo($fbProductId)
    {
        $requestFields = [
            'price'
        ];

        $request = [
            'access_token' => $this->accessToken,
            'fields' => implode(',', $requestFields),
        ];
        $response = $this->callApi('GET', "{$fbProductId}", $request);
        return json_decode($response->getBody(), true);
    }

    /**
     * Get product by retailer Id
     *
     * @param mixed $catalogId
     * @param bool|int|string $retailerId
     * @return array|mixed|object
     * @throws GuzzleException
     */
    public function getProductByRetailerId($catalogId, $retailerId)
    {
        $request = [
            'access_token' => $this->accessToken,
            'filter' => '{"retailer_id":{"eq":"' . $retailerId . '"}}',
        ];
        $response = $this->callApi('GET', "{$catalogId}/products", $request);
        return json_decode($response->getBody(), true);
    }

    /**
     * Get products by Facebook product Ids
     *
     * @param mixed $catalogId
     * @param array $fbProductIds
     * @return array|mixed|object
     * @throws GuzzleException
     */
    public function getProductsByFacebookProductIds($catalogId, array $fbProductIds)
    {
        $request = [
            'access_token' => $this->accessToken,
            'filter' => '{"product_item_id":{"is_any":' . json_encode($fbProductIds) . '}}',
        ];
        $response = $this->callApi('GET', "{$catalogId}/products", $request);
        return json_decode($response->getBody(), true);
    }

    /**
     * Get product errors
     *
     * @param mixed $fbProductId
     * @return array|mixed|object
     * @throws GuzzleException
     */
    public function getProductErrors($fbProductId)
    {
        $request = [
            'access_token' => $this->accessToken,
            'fields' => 'errors'
        ];
        $response = $this->callApi('GET', "{$fbProductId}", $request);
        return json_decode($response->getBody(), true);
    }

    /**
     * Get catalog diagnostics
     *
     * @param mixed $catalogId
     * @return mixed
     * @throws GuzzleException
     */
    public function getCatalogDiagnostics($catalogId)
    {
        $request = [
            'access_token' => $this->accessToken,
            'fields' => 'diagnostics'
        ];
        $response = $this->callApi('GET', "{$catalogId}", $request);
        return json_decode($response->getBody(), true);
    }
}
