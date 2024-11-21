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
     * Return information about the cart items
     *
     * @param CartInterface $quote
     * @return array
     */
    private function getCartContents(CartInterface $quote): array
    {
        if (!$quote) {
            return [];
        }

        $contents = [];
        $items = $quote->getAllVisibleItems();

        foreach ($items as $item) {
            $product = $item->getProduct();
            $contents[] = [
                'product_id' => $this->magentoDataHelper->getContentId($product),
                'quantity' => (int) $item->getQty(),
                'item_price' => $item->getPrice(),
            ];
        }
        return $contents;
    }

    /**
     * Return the ids of the items added to the cart
     *
     * @param CartInterface $quote
     * @return array
     */
    private function getCartContentIds(CartInterface $quote): array
    {
        if (!$quote) {
            return [];
        }
        $contentIds = [];

        $items = $quote->getAllVisibleItems();
        foreach ($items as $item) {
            $contentIds[] = $this->magentoDataHelper->getContentId($item->getProduct());
        }
        return $contentIds;
    }

    /**
     * Get cart categories by quote
     *
     * @param CartInterface $quote
     * @return string
     */
    private function getCartCategories(CartInterface $quote): string
    {
        if (!$quote) {
            return '';
        }

        $items = $quote->getAllVisibleItems();
        $categoryIds = [];
        foreach ($items as $item) {
            $product = $item->getProduct();
            if ($product->getCategoryIds()) {
                $categoryIds[] = $product->getCategoryIds();
            }
        }
        
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
            return [
                'currency' => $this->magentoDataHelper->getCurrency(),
                'value' => $this->magentoDataHelper->getCartTotal($quote),
                'content_ids' => $this->getCartContentIds($quote),
                'num_items' => $this->magentoDataHelper->getCartNumItems($quote),
                'contents' => $this->getCartContents($quote),
                'content_type' => $this->getContentTypeByQuote($quote),
                'content_category' => $this->getCartCategories($quote)

            ];
        } catch (NoSuchEntityException $e) {
            return [];
        }
    }
}
