<?php
declare(strict_types=1);

namespace Meta\Conversion\Block\Pixel;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template\Context;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Conversion\Helper\MagentoDataHelper;

/**
 * @api
 */
class CustomerRegistrationSuccess extends Common
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
     * Returns content type
     *
     * @return string
     */
    public function getContentType()
    {
        return "customer_registration";
    }

    /**
     * Returns event name
     *
     * @return string
     */
    public function getEventToObserveName()
    {
        return 'facebook_businessextension_ssapi_customer_registration_success';
    }

    public function getEventId(): ?string
    {
        $eventIds = $this->customerSession->getEventIds();
        if (is_array($eventIds) && array_key_exists($this->getEventToObserveName(), $eventIds)) {
            return $eventIds[$this->getEventToObserveName()];
        }

        return null;
    }
}
