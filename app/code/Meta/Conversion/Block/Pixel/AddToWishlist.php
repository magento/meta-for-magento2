<?php
declare(strict_types=1);

namespace Meta\Conversion\Block\Pixel;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template\Context;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Conversion\Helper\MagentoDataHelper;

/**
 * @api
 */
class AddToWishlist extends Common
{

    private $customerSession;

    public function __construct(
        Context $context,
        FBEHelper $fbeHelper,
        MagentoDataHelper $magentoDataHelper,
        SystemConfig $systemConfig,
        Escaper $escaper,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $fbeHelper, $magentoDataHelper, $systemConfig, $escaper, $checkoutSession, $data);
        $this->customerSession = $customerSession;
    }

    /**
     * Returns event name
     *
     * @return string
     */
    public function getEventToObserveName()
    {
        return 'facebook_businessextension_ssapi_add_to_wishlist';
    }

    public function getEventId(): ?string
    {
        $eventIds = $this->customerSession->getEventIds();

        if (is_array($eventIds) && array_key_exists('eventIds', $eventIds) &&
            is_array($eventIds['eventIds']) && array_key_exists($this->getEventToObserveName(), $eventIds['eventIds'])) {

            return (string) $eventIds['eventIds'][$this->getEventToObserveName()];
        }

        return null;
    }
}
