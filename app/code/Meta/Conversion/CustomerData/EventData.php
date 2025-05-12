<?php
declare(strict_types=1);

namespace Meta\Conversion\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session as CustomerSession;

class EventData implements SectionSourceInterface
{

    public function __construct(
        private readonly CustomerSession $customerSession)
    { }

    public function getSectionData()
    {
        return ['eventIds' => $this->customerSession->getMetaEventIds() ?: []];
    }
}
