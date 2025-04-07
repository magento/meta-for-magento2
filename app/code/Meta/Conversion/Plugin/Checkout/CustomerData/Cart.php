<?php
declare(strict_types=1);

namespace Meta\Conversion\Plugin\Checkout\CustomerData;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Meta\Conversion\Helper\MagentoDataHelper;

/**
 * Set meta_payload in localstorage of cart for payment info add
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Cart
{
    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var MagentoDataHelper
     */
    private MagentoDataHelper $magentoDataHelper;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * Constructor
     *
     * @param MagentoDataHelper $magentoDataHelper
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        MagentoDataHelper $magentoDataHelper,
        CheckoutSession $checkoutSession
    ) {
        $this->magentoDataHelper = $magentoDataHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Get active quote
     *
     * @return Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuote(): Quote
    {
        if (null === $this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }
        return $this->quote;
    }

    /**
     * Add Payment Info payload on local storage
     *
     * @param \Magento\Checkout\CustomerData\Cart $subject
     * @param array $result
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, $result)
    {
        $result['meta_payload'] = $this->magentoDataHelper->getCartPayload($this->getQuote());
        return $result;
    }
}
