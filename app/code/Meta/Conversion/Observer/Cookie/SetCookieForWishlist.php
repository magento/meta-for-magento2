<?php
declare(strict_types=1);

namespace Meta\Conversion\Observer\Cookie;

use Magento\Framework\Escaper;
use Meta\Conversion\Helper\MagentoDataHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Meta\Conversion\Observer\Common;
use Magento\Catalog\Model\Product;

class SetCookieForWishlist implements ObserverInterface
{
    public const META_PIXEL_WISHLIST_COOKIE_NAME = 'event_add_to_wishlist';

    /**
     * @var MagentoDataHelper
     */
    private $magentoDataHelper;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var Common
     */
    private Common $common;

    /**
     * Constructor Observer
     *
     * @param MagentoDataHelper $magentoDataHelper
     * @param Escaper $escaper
     * @param Common $common
     */
    public function __construct(
        MagentoDataHelper $magentoDataHelper,
        Escaper $escaper,
        Common $common
    ) {
        $this->magentoDataHelper = $magentoDataHelper;
        $this->escaper = $escaper;
        $this->common = $common;
    }

    /**
     * Execute action method for the add to wishlist Observer
     *
     * @param Observer $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        // For cart page - "Move To Wishlist"
        $items = $observer->getEvent()->getItems();
        $payload = [];
        foreach ($items as $item) {
            $product = $item->getProduct();
            $contents = [
                [
                    'id' => $this->magentoDataHelper->getContentId($product),
                    'quantity' => 1
                ]
            ];
            $payload = [
                'content_name'     => $this->escaper->escapeUrl($product->getName()),
                'content_category' => $this->magentoDataHelper->getCategoriesNameById($product->getCategoryIds()),
                'content_ids'      => [$this->magentoDataHelper->getContentId($product)],
                'contents'         => $contents,
                'value'            => (float) $this->getPrice($product),
                'currency'         => $this->magentoDataHelper->getCurrency(),
            ];
            break;
        }
        $this->common->setCookieForMetaPixel(self::META_PIXEL_WISHLIST_COOKIE_NAME, $payload);

        return $this;
    }

    /**
     * Get Price for Grouped product
     *
     * @param Product $product
     * @return float|int
     */
    private function getPrice($product)
    {
        $value = $this->magentoDataHelper->getValueForProduct($product);
        $lowestprice = 0;
        $count = 0;
        if ($value <= 0 && $product->getTypeId() == 'grouped') {
            $usedProds = $product->getTypeInstance()->getAssociatedProducts($product);
            foreach ($usedProds as $child) {
                if ($child->getId() != $product->getId()) {
                    if ($count == 0) {
                        $lowestprice = $child->getPrice();
                    }

                    if ($child->getPrice() < $lowestprice) {
                        $lowestprice = $child->getPrice();
                    }
                }
                $count++;
            }
            $value = $lowestprice;
        }
        return $value;
    }
}
