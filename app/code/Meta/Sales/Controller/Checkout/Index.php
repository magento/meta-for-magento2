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
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Quote\Api\Data\ProductOptionInterfaceFactory;
use Magento\ConfigurableProduct\Api\Data\ConfigurableItemOptionValueInterfaceFactory;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Sales\Helper\OrderHelper;
use Exception;

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
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var OrderHelper
     */
    private OrderHelper $orderHelper;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var ConfigurableType
     */
    private ConfigurableType $configurableType;

    /**
     * @var ProductOptionInterfaceFactory
     */
    private ProductOptionInterfaceFactory $productOptionInterfaceFactory;

    /**
     * @var ConfigurableItemOptionValueInterfaceFactory
     */
    private ConfigurableItemOptionValueInterfaceFactory $configurableItemOptionValueFactory;

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
     * @param ProductRepositoryInterface $productRepository
     * @param ConfigurableType $configurableType
     * @param ProductOptionInterfaceFactory $productOptionInterfaceFactory
     * @param ConfigurableItemOptionValueInterfaceFactory $configurableItemOptionValueFactory
     */
    public function __construct(
        QuoteFactory             $quoteFactory,
        QuoteIdMaskFactory       $quoteIdMaskFactory,
        AddressFactory           $quoteAddressFactory,
        CartRepositoryInterface  $quoteRepository,
        GuestCartItemRepository  $guestCartItemRepository,
        CartItemInterfaceFactory $cartItemInterfaceFactory,
        GuestCouponManagement    $guestCouponManagement,
        CheckoutSession          $checkoutSession,
        Http                     $httpRequest,
        RedirectFactory          $resultRedirectFactory,
        Authenticator            $authenticator,
        FBEHelper                $fbeHelper,
        OrderHelper              $orderHelper,
        ProductRepositoryInterface $productRepository,
        ConfigurableType         $configurableType,
        ProductOptionInterfaceFactory $productOptionInterfaceFactory,
        ConfigurableItemOptionValueInterfaceFactory $configurableItemOptionValueFactory
    ) {
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
        $this->fbeHelper = $fbeHelper;
        $this->orderHelper = $orderHelper;
        $this->productRepository = $productRepository;
        $this->configurableType = $configurableType;
        $this->productOptionInterfaceFactory = $productOptionInterfaceFactory;
        $this->configurableItemOptionValueFactory = $configurableItemOptionValueFactory;
    }

    /**
     * Execute action based on request.
     *
     * @return Redirect
     * @throws LocalizedException
     */
    public function execute()
    {
        $ebid = $this->httpRequest->getParam('ebid');
        $productsParam = $this->httpRequest->getParam('products');
        $coupon = $this->httpRequest->getParam('coupon');
        $redirectPath = $this->httpRequest->getParam('redirect');

        $storeId = (int)$this->orderHelper->getStoreIdByExternalBusinessId($ebid);
        $products = explode(',', $productsParam);

        $quote = $this->createQuote($storeId);
        $cartId = $this->getMaskedCartId($quote);

        foreach ($products as $productItem) {
            $this->addProductToCart($productItem, $cartId, $storeId);
        }

        if ($coupon) {
            $this->applyCouponToCart($coupon, $cartId, $storeId);
        }

        $this->checkoutSession->replaceQuote($quote);
        return $this->redirectToPath($redirectPath);
    }

    /**
     * Create a new quote.
     *
     * @param int $storeId
     * @return Quote
     * @throws CouldNotSaveException
     */
    private function createQuote(int $storeId): Quote
    {
        try {
            $quote = $this->quoteFactory->create();
            $quote->setStoreId($storeId);
            $quote->setBillingAddress($this->quoteAddressFactory->create());
            $quote->setShippingAddress($this->quoteAddressFactory->create());
            $quote->setCustomerIsGuest(true);
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $this->quoteRepository->save($quote);
            return $quote;
        } catch (Exception $e) {
            $this->logExceptionToMeta($e, $storeId, 'quote_save_exception');
            throw new CouldNotSaveException(__("The quote can't be created."));
        }
    }

    /**
     * Get masked cart ID.
     *
     * @param Quote $quote
     * @return string
     */
    private function getMaskedCartId(Quote $quote): string
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create();
        $quoteIdMask->setQuoteId($quote->getId())->save();
        return $quoteIdMask->getMaskedId();
    }

    /**
     * Add product to cart.
     *
     * @param string $productItem
     * @param string $cartId
     * @param int $storeId
     * @return void
     */
    private function addProductToCart(string $productItem, string $cartId, int $storeId): void
    {
        try {
            [$sku, $quantity] = explode(':', $productItem);
            $quantity = (int)$quantity;

            $product = $this->productRepository->get($sku, false, $storeId, true);

            [$parentSku, $configurableOptions] = $this->getConfigurableOptions($product, $storeId);

            $cartItem = $this->cartItemInterfaceFactory->create();
            $cartItem->setSku($sku);
            $cartItem->setQty($quantity);
            $cartItem->setQuoteId($cartId);

            if ($configurableOptions) {
                $cartItem->setSku($parentSku);
                $productOption = $this->productOptionInterfaceFactory->create();
                $extensionAttributes = $productOption->getExtensionAttributes();
                $extensionAttributes->setConfigurableItemOptions($configurableOptions);
                $productOption->setExtensionAttributes($extensionAttributes);
                $cartItem->setProductOption($productOption);
            }

            $this->guestCartItemRepository->save($cartItem);
        } catch (Exception $e) {
            $this->logExceptionToMeta($e, $storeId, 'error_adding_item', [
                'cart_id' => $cartId,
                'sku' => $sku,
                'quantity' => $quantity,
            ]);
        }
    }

    /**
     * Apply coupon to cart.
     *
     * @param string $coupon
     * @param string $cartId
     * @param int $storeId
     * @return void
     */
    private function applyCouponToCart(string $coupon, string $cartId, int $storeId): void
    {
        try {
            $this->guestCouponManagement->set($cartId, $coupon);
        } catch (Exception $e) {
            $this->logExceptionToMeta($e, $storeId, 'error_adding_coupon', [
                'cart_id' => $cartId,
                'coupon' => $coupon,
            ]);
        }
    }

    /**
     * Redirect to path.
     *
     * @param string|null $redirectPath
     * @return Redirect
     */
    private function redirectToPath(?string $redirectPath): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($redirectPath ?: 'checkout');
        return $resultRedirect;
    }

    /**
     * Get configurable options for a product.
     *
     * @param ProductInterface $product
     * @param int $storeId
     * @return array
     */
    private function getConfigurableOptions(ProductInterface $product, int $storeId): array
    {
        $parentIds = $this->configurableType->getParentIdsByChild($product->getId());
        if (empty($parentIds)) {
            return [null, null];
        }

        $parentId = $parentIds[0];
        $parentProduct = $this->productRepository->getById($parentId, false, $storeId);
        $configurableAttributes = $this->configurableType->getConfigurableAttributes($parentProduct);
        $configurableOptions = [];

        foreach ($configurableAttributes as $attribute) {
            $attributeId = (int)$attribute->getAttributeId();
            $productAttribute = $attribute->getProductAttribute();
            $attributeValue = $product->getData($productAttribute->getAttributeCode());
            if ($attributeValue === null) {
                continue;
            }
            $optionId = $productAttribute->getSource()->getOptionId($attributeValue);

            $optionValue = $this->configurableItemOptionValueFactory->create();
            $optionValue->setOptionId($attributeId);
            $optionValue->setOptionValue($optionId);
            $configurableOptions[] = $optionValue;
        }

        return [$parentProduct->getSku(), $configurableOptions];
    }

    /**
     * Log exception to Meta.
     *
     * @param Exception $e
     * @param int $storeId
     * @param string $eventType
     * @param array $extraData
     * @return void
     */
    private function logExceptionToMeta(Exception $e, int $storeId, string $eventType, array $extraData = []): void
    {
        $this->fbeHelper->logExceptionImmediatelyToMeta(
            $e,
            [
                'store_id' => $storeId,
                'event' => 'meta_checkout_url',
                'event_type' => $eventType,
                'extra_data' => $extraData,
            ]
        );
    }
}
