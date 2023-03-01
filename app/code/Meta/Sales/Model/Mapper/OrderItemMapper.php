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
declare(strict_types=1);

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
        $pricePerUnit = $item['price_per_unit']['amount'];

        $originalPrice = $this->getPriceBeforeDiscount($item['product_id'], $storeId) ?? $pricePerUnit;

        $quantity = $item['quantity'];
        $taxAmount = $item['tax_details']['estimated_tax']['amount'];

        $rowTotal = $pricePerUnit * $quantity;
        $promotionDetails = $item['promotion_details']['data'] ?? null;
        $discountAmount = 0;
        if ($promotionDetails) {
            foreach ($promotionDetails as $promotionDetail) {
                if ($promotionDetail['target_granularity'] === 'order_level') {
                    $discountAmount += $promotionDetail['applied_amount']['amount'];
                }
            }
        }

        /** @var OrderItem $orderItem */
        $orderItem = $this->orderItemFactory->create();
        $orderItem->setProductId($product->getId())
            ->setSku($product->getSku())
            ->setName($product->getName())
            ->setQtyOrdered($quantity)
            ->setBasePrice($originalPrice)
            ->setOriginalPrice($originalPrice)
            ->setPrice($originalPrice)
            ->setTaxAmount($taxAmount)
            ->setRowTotal($rowTotal)
            ->setDiscountAmount($discountAmount)
            ->setBaseDiscountAmount($discountAmount)
            ->setProductType($product->getTypeId());

        if ($rowTotal != 0) {
            $orderItem->setTaxPercent(round(($taxAmount / $rowTotal) * 100, 2));
        }

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
     * Get price before discount from api loaded facebook product info
     *
     * @param string|int $fbProductId
     * @param int $storeId
     * @return string|bool
     */
    private function getPriceBeforeDiscount($fbProductId, int $storeId)
    {
        try {
            $this->graphAPIAdapter
                ->setDebugMode($this->systemConfig->isDebugMode($storeId))
                ->setAccessToken($this->systemConfig->getAccessToken($storeId));
            $productInfo = $this->graphAPIAdapter->getProductInfo($fbProductId);
            if ($productInfo && array_key_exists('price', $productInfo)) {
                //this returns amount without $, ex: $100.00 -> 100.00
                return substr($productInfo['price'], 1);
            }
        } catch (GuzzleException $e) {
            $this->logger->critical($e->getMessage());
        }
        return false;
    }
}
