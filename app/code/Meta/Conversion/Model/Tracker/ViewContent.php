<?php

declare(strict_types=1);

namespace Meta\Conversion\Model\Tracker;

use Magento\Framework\Exception\NoSuchEntityException;
use Meta\BusinessExtension\Helper\MagentoDataHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Meta\Conversion\Api\TrackerInterface;

class ViewContent implements TrackerInterface
{

    private const EVENT_TYPE = "ViewContent";

    /**
     * @var MagentoDataHelper
     */
    private $magentoDataHelper;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param MagentoDataHelper $magentoDataHelper
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        MagentoDataHelper $magentoDataHelper,
        ProductRepositoryInterface $productRepository
    ) {
        $this->magentoDataHelper = $magentoDataHelper;
        $this->productRepository = $productRepository;
    }

    /**
     * @inheritDoc
     */
    public function getEventType(): string
    {
        return self::EVENT_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function getPayload(array $params): array
    {
        $productId = $params['productId'];
        try {
            $product = $this->productRepository->getById($productId);
            $contentId = $this->magentoDataHelper->getContentId($product);
            return [
                'currency' => $this->magentoDataHelper->getCurrency(),
                'value' => $this->magentoDataHelper->getValueForProduct($product),
                'content_ids' => [$contentId],
                'content_category' => $this->magentoDataHelper->getCategoriesForProduct($product),
                'content_name' => $product->getName(),
                'contents' => [
                    [
                        'id' => $contentId,
                        'item_price' => $this->magentoDataHelper->getValueForProduct($product)
                    ]
                ],
                'content_type' => $this->magentoDataHelper->getContentType($product)
            ];
        } catch (NoSuchEntityException $e) {
            return [];
        }
    }
}
