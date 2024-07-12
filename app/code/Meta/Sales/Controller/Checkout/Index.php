<?php

declare(strict_types=1);

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

namespace Meta\Sales\Controller\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Quote\Model\GuestCart\GuestCartItemRepository;
use Magento\Quote\Model\GuestCart\GuestCouponManagement;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Sales\Helper\OrderHelper;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class Index implements HttpGetActionInterface
{
    /**
     * @var QuoteFactory
     */
    private QuoteFactory $quoteFactory;

    /**
     * @var QuoteIdMaskFactory
     */
    private QuoteIdMaskFactory $quoteIdMaskFactory;

    /**
     * @var AddressFactory
     */
    private AddressFactory $quoteAddressFactory;

    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $quoteRepository;

    /**
     * @var GuestCartItemRepository
     */
    private GuestCartItemRepository $guestCartItemRepository;

    /**
     * @var CartItemInterfaceFactory
     */
    private CartItemInterfaceFactory $cartItemInterfaceFactory;

    /**
     * @var GuestCouponManagement
     */
    private GuestCouponManagement $guestCouponManagement;

    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var Http
     */
    private Http $httpRequest;

    /**
     * @var RedirectFactory
     */
    private RedirectFactory $resultRedirectFactory;

    /**
     * @var Authenticator
     */
    private Authenticator $authenticator;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var OrderHelper
     */
    private OrderHelper $orderHelper;


    /**
     * @param QuoteFactory $quoteFactory
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param AddressFactory $quoteAddressFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param GuestCartItemRepository $guestCartItemRepository
     * @param CartItemInterfaceFactory $cartItemInterfaceFactory
     * @param GuestCouponManagement $guestCouponManagement
     * @param CheckoutSession $checkoutSession
     * @param Http $httpRequest
     * @param RedirectFactory $resultRedirectFactory
     * @param Authenticator $authenticator
     * @param FBEHelper $fbeHelper
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        QuoteFactory             $quoteFactory,
        QuoteIdMaskFactory       $quoteIdMaskFactory,
        AddressFactory           $quoteAddressFactory,
        CartRepositoryInterface  $quoteRepository,
        GuestCartitemRepository  $guestCartItemRepository,
        CartItemInterfaceFactory $cartItemInterfaceFactory,
        GuestCouponManagement    $guestCouponManagement,
        CheckoutSession          $checkoutSession,
        Http                     $httpRequest,
        RedirectFactory          $resultRedirectFactory,
        Authenticator            $authenticator,
        FBEHelper                $fbeHelper,
        OrderHelper              $orderHelper
    )
    {
        $this->quoteFactory = $quoteFactory;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->quoteRepository = $quoteRepository;
        $this->guestCartItemRepository = $guestCartItemRepository;
        $this->cartItemInterfaceFactory = $cartItemInterfaceFactory;
        $this->guestCouponManagement = $guestCouponManagement;
        $this->checkoutSession = $checkoutSession;
        $this->httpRequest = $httpRequest;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->authenticator = $authenticator;
        $this->fbeHelper = $fbeHelper;
        $this->orderHelper = $orderHelper;
    }

    /**
     * Execute action based on httpRequest.
     *
     * IMPORTANT: Signatures must be URL-Encoded after being Base64 Encoded, or verification will fail.
     *
     * @return Redirect
     * @throws LocalizedException
     */
    public function execute()
    {
        $externalBusinessId = $this->httpRequest->getParam('external_business_id');
        $products = explode(',', $this->httpRequest->getParam('products'));
        $coupon = $this->httpRequest->getParam('coupon');
        $signature = $this->httpRequest->getParam('signature');

        $storeId = $this->orderHelper->getStoreIdByExternalBusinessId($externalBusinessId);

        // Verify signature
        $uri = $this->httpRequest->getRequestUri();
        $query_string = parse_url($uri, PHP_URL_QUERY);
        $params = [];
        parse_str($query_string, $params);
        unset($params['signature']);
        $new_query_string = http_build_query($params);
        $validation_uri = urldecode(str_replace($query_string, $new_query_string, $uri));

        if (!$this->authenticator->verifySignature($validation_uri, $signature)) {
            $e = new LocalizedException(__('RSA Signature Validation Failed'));
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'meta_checkout_url',
                    'event_type' => 'rsa_signature_validation_error',
                    'extra_data' => [
                        'request_uri' => $uri,
                        'request_signature' => $signature,
                        'validation_uri' => $validation_uri
                    ]
                ]
            );
            throw $e;
        }

        // Create cart
        /**
         * @var QuoteIdMask $quoteIdMask
         */
        $quoteIdMask = $this->quoteIdMaskFactory->create();
        /**
         * @var Quote $quote
         */
        $quote = $this->quoteFactory->create();
        $quote->setStoreId($storeId);
        $quote->setBillingAddress($this->quoteAddressFactory->create());
        $quote->setShippingAddress($this->quoteAddressFactory->create());
        $quote->setCustomerIsGuest(1);

        try {
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'meta_checkout_url',
                    'event_type' => 'quote_save_exception'
                ]
            );
            throw new CouldNotSaveException(__("The quote can't be created."));
        }

        $quoteIdMask->setQuoteId($quote->getId())->save();
        $cartId = $quoteIdMask->getMaskedId();

        // Add items to cart
        foreach ($products as $product) {
            try {
                list($sku, $quantity) = explode(':', $product);
                $quantity = (int)$quantity;

                $cartItem = $this->cartItemInterfaceFactory->create();
                $cartItem->setSku($sku);
                $cartItem->setQty($quantity);
                $cartItem->setQuoteId($cartId);

                $this->guestCartItemRepository->save($cartItem);
            } catch (\Exception $e) {
                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $e,
                    [
                        'store_id' => $storeId,
                        'event' => 'meta_checkout_url',
                        'event_type' => 'error_adding_item',
                        'extra_data' => [
                            'cart_id' => $cartId,
                            'sku' => $sku,
                            'quantity' => $quantity
                        ]
                    ]
                );
            }
        }

        // Add coupon to cart
        if ($coupon) {
            try {
                $this->guestCouponManagement->set($cartId, $coupon);
            } catch (\Exception $e) {
                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $e,
                    [
                        'store_id' => $storeId,
                        'event' => 'meta_checkout_url',
                        'event_type' => 'error_adding_coupon',
                        'extra_data' => [
                            'cart_id' => $cartId,
                            'coupon' => $coupon
                        ]
                    ]
                );
            }
        }

        // Redirect to checkout
        $this->checkoutSession->replaceQuote($quote);
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('checkout/cart');

        return $resultRedirect;
    }
}
