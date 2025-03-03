<?php

declare(strict_types=1);

namespace Meta\Conversion\Model\Tracker;

use Magento\Framework\Exception\NoSuchEntityException;
use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\Conversion\Api\TrackerInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

class InitiateCheckout implements TrackerInterface
{
    private const EVENT_TYPE = 'InitiateCheckout';

    /**
     * @var MagentoDataHelper
     */
    private $magentoDataHelper;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CollectionFactory
     */
    private $categoryCollection;

    /**
     * @param MagentoDataHelper $magentoDataHelper
     * @param CartRepositoryInterface $cartRepository
     * @param CollectionFactory $categoryCollection
     */
    public function __construct(
        MagentoDataHelper $magentoDataHelper,
        CartRepositoryInterface $cartRepository,
        CollectionFactory $categoryCollection
    ) {
        $this->magentoDataHelper = $magentoDataHelper;
        $this->cartRepository = $cartRepository;
        $this->categoryCollection = $categoryCollection;
    }

    /**
     * @inheritDoc
     */
    public function getEventType(): string
    {
        return self::EVENT_TYPE;
    }

    /**
     * Get cart categories by categoryIds
     *
     * @param array $categoryIds
     * @return string
     */
    private function getCartCategories(array $categoryIds): string
    {

        /** Handle products without categories assigned */
        if (empty($categoryIds)) {
            return '';
        }
        $categoryIds = array_merge(...$categoryIds);

        $categoryNames = [];
        $categories = $this->categoryCollection->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', ['in' => $categoryIds]);
        foreach ($categories as $category) {
            $categoryNames[] = $category->getName();
        }

        return implode(',', $categoryNames);
    }

    /**
     * Get content type by quote
     *
     * @param CartInterface $quote
     * @return string
     */
    private function getContentTypeByQuote(CartInterface $quote): string
    {
        if (!$quote) {
            return '';
        }

        return 'product';
    }

    /**
     * @inheritDoc
     */
    public function getPayload(array $params): array
    {
        try {
            $quoteId = $params['quoteId'];
            $quote = $this->cartRepository->get((int) $quoteId);
            $cartData = $this->prepareData($quote);
            return [
                'currency' => $this->magentoDataHelper->getCurrency(),
                'value' => $this->magentoDataHelper->getCartTotal($quote),
                'content_ids' => $cartData['content_ids'],
                'num_items' => $this->magentoDataHelper->getCartNumItems($quote),
                'contents' => $cartData['contents'],
                'content_type' => $this->getContentTypeByQuote($quote),
                'content_category' => $this->getCartCategories($cartData['category_ids'])

            ];
        } catch (NoSuchEntityException $e) {
            return [];
        }
    }

    /**
     * @param CartInterface $quote
     * @return array[]
     */
    private function prepareData(CartInterface $quote): array
    {
        $categoryIds = [];
        $contentIds = [];
        $contents = [];

        foreach ($quote->getAllVisibleItems() as $item) {

            $product = $item->getProduct();
            if ($product->getCategoryIds()) {
                $categoryIds[] = $product->getCategoryIds();
            }
            $contentIds[] = $this->magentoDataHelper->getContentId($item->getProduct());
            $contents[] = [
                'product_id' => $this->magentoDataHelper->getContentId($product),
                'quantity' => (int) $item->getQty(),
                'item_price' => $item->getPrice()
            ];

        }

        return ['categoryIds' => $categoryIds, 'contentIds' => $contentIds, 'contents' => $contents];
    }
}
