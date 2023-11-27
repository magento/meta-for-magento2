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

namespace Meta\Sales\Model\Mapper;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderItemInterfaceFactory;
use Magento\Sales\Model\Order\Item as OrderItem;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Helper\Product\Identifier as ProductIdentifier;
use Psr\Log\LoggerInterface;

/**
 * Map facebook order item data to magento order item
 */
class OrderItemMapper
{
    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var GraphAPIAdapter
     */
    private GraphAPIAdapter $graphAPIAdapter;

    /**
     * @var ProductIdentifier
     */
    private ProductIdentifier $productIdentifier;

    /**
     * @var ProductRepository
     */
    private ProductRepository $productRepository;

    /**
     * @var ConfigurableType
     */
    private ConfigurableType $configurableType;

    /**
     * @var OrderItemInterfaceFactory
     */
    private OrderItemInterfaceFactory $orderItemFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphAPIAdapter
     * @param LoggerInterface $logger
     * @param ProductIdentifier $productIdentifier
     * @param ProductRepository $productRepository
     * @param ConfigurableType $configurableType
     * @param OrderItemInterfaceFactory $orderItemFactory
     */
    public function __construct(
        SystemConfig $systemConfig,
        GraphAPIAdapter $graphAPIAdapter,
        LoggerInterface $logger,
        ProductIdentifier $productIdentifier,
        ProductRepository $productRepository,
        ConfigurableType $configurableType,
        OrderItemInterfaceFactory $orderItemFactory
    ) {
        $this->systemConfig = $systemConfig;
        $this->graphAPIAdapter = $graphAPIAdapter;
        $this->logger = $logger;
        $this->productIdentifier = $productIdentifier;
        $this->productRepository = $productRepository;
        $this->configurableType = $configurableType;
        $this->orderItemFactory = $orderItemFactory;
    }

    /**
     * Map facebook order item data to magento order item
     *
     * @param array $item
     * @param int $storeId
     * @return OrderItem
     * @throws LocalizedException
     */
    public function map(array $item, int $storeId): OrderItem
    {
        $product = $this->productIdentifier->getProductByFacebookRetailerId($item['retailer_id']);
        $productInfo = $this->getProductInfo($item['product_id'], $storeId);

        $quantity = $item['quantity'];

        // strike-through price if available, otherwise list price
        $originalPrice = $productInfo['price'];

        // sale price if available, otherwise list price
        $price = $productInfo['sale_price'] ?? $originalPrice;

        // actual price, including applied discounts
        $discountPrice = $item['price_per_unit']['amount'];

        $rowTotal = $price * $quantity;
        $rowWeight = $product->getWeight() * $quantity;
        $rowTaxAmount = $item['tax_details']['estimated_tax']['amount'];
        $rowDiscountAmount = ($price - $discountPrice) * $quantity;

        $discountAmount = $price - $discountPrice;
        $discountPercent = round(($discountAmount / $price) * 100, 2);

        $taxPercent = round($rowTaxAmount / ($discountPrice * $quantity) * 100, 2);
        $priceInclTax = round(($price * (100 + $taxPercent) / 100), 2);
        $rowTotalInclTax = round(($priceInclTax * $quantity), 2);

        // Dynamic Checkout:
        // set applied_rule_ids

        /** @var OrderItem $orderItem */
        $orderItem = $this->orderItemFactory->create();

        $orderItem
            ->setProductId($product->getId())
            ->setSku($product->getSku())
            ->setName($product->getName())
            ->setQtyOrdered($quantity)
            ->setOriginalPrice($originalPrice)
            ->setBaseOriginalPrice($originalPrice)
            ->setPrice($price)
            ->setBasePrice($price)
            ->setPriceInclTax($priceInclTax)
            ->setBasePriceInclTax($priceInclTax)
            ->setTaxAmount($rowTaxAmount)
            ->setBaseTaxAmount($rowTaxAmount)
            ->setTaxPercent($taxPercent)
            ->setRowTotal($rowTotal)
            ->setBaseRowTotal($rowTotal)
            ->setRowTotalInclTax($rowTotalInclTax)
            ->setBaseRowTotalInclTax($rowTotalInclTax)
            ->setRowWeight($rowWeight)
            ->setDiscountAmount($rowDiscountAmount)
            ->setBaseDiscountAmount($rowDiscountAmount)
            ->setDiscountPercent($discountPercent)
            ->setProductType($product->getTypeId())
            ->setWeight($product->getWeight())
            ->setIsVirtual(false)
            ->setIsQtyDecimal(false)
            ->setStoreId($storeId)
            ->setDiscountTaxCompensationAmount(0)
            ->setBaseDiscountTaxCompensationAmount(0);

        $productOptions = $this->getProductOptions($product, $orderItem);
        if ($productOptions) {
            $orderItem->setProductOptions($productOptions);
        }

        return $orderItem;
    }

    /**
     * Get configurable product options such as size and color
     *
     * @param ProductInterface $product
     * @param OrderItem $orderItem
     * @return array|null
     */
    private function getProductOptions(ProductInterface $product, OrderItem $orderItem): ?array
    {
        $configurableProducts = $this->configurableType->getParentIdsByChild($product->getId());
        if (!isset($configurableProducts[0])) {
            return null;
        }
        $parentId = $configurableProducts[0];
        try {
            $parentProduct = $this->productRepository->getById($parentId, false, $product->getStoreId());
            $configurableAttributes = $this->configurableType->getConfigurableAttributes($parentProduct);

            $superAttributes = [];
            $attributesInfo = [];

            foreach ($configurableAttributes as $attribute) {
                $attributeId = (int)$attribute->getAttributeId();
                $productAttribute = $attribute->getProductAttribute();
                $attributeValue = $product->getData($productAttribute->getAttributeCode());
                $optionId = $productAttribute->getSource()->getOptionId($attributeValue);
                $optionText = $productAttribute->getSource()->getOptionText($attributeValue);
                $superAttributes[$attributeId] = $optionId;
                $attributesInfo[] = [
                    'label' => __($productAttribute->getStoreLabel()),
                    'value' => $optionText,
                    'option_id' => $attributeId,
                    'option_value' => $optionId,
                ];
            }

            return [
                'info_buyRequest' => [
                    'qty' => $orderItem->getQtyOrdered(),
                    'super_attribute' => $superAttributes,
                ],
                'attributes_info' => $attributesInfo,
                'simple_sku' => $product->getSku(),
                'simple_name' => $product->getName(),
            ];
        } catch (Exception $e) {
            $this->logger->critical($e);
            return null;
        }
    }

    /**
     * Get product info for the provided product.
     *
     * @param string|int $fbProductId
     * @param int $storeId
     * @return string|bool
     */
    private function getProductInfo($fbProductId, int $storeId)
    {
        $this->graphAPIAdapter
            ->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($this->systemConfig->getAccessToken($storeId));

        $productInfo = $this->graphAPIAdapter->getProductInfo($fbProductId);

        // strip the currency from the prices

        if ($productInfo) {
            if (array_key_exists('price', $productInfo)) {
                $productInfo['price'] = substr($productInfo['price'], 1);
            }

            if (array_key_exists('sale_price', $productInfo)) {
                $productInfo['sale_price'] = substr($productInfo['sale_price'], 1);
            }
        }

        return $productInfo;
    }
}
