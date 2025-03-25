<?php
declare(strict_types=1);

namespace Meta\Conversion\Observer\Tracker;

use Magento\Framework\Event\ObserverInterface;
use Meta\Conversion\Model\CapiTracker;
use Meta\Conversion\Model\Tracker\PageView as PageViewTracker;
use Meta\Conversion\Model\Tracker\ViewCategory as ViewCategoryTracker;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Registry;

class PageView implements ObserverInterface
{
    const PAGE_VIEW_EVENT_NAME = 'facebook_businessextension_ssapi_page_view';


    public function __construct(
        private readonly PageViewTracker $pageViewTracker,
        private readonly CapiTracker $capiTracker
    ) { }

    public function execute(\Magento\Framework\Event\Observer $observer): void
    {

        $pageViewPayload = [];
        $this->capiTracker->execute($pageViewPayload, self::PAGE_VIEW_EVENT_NAME,  $this->pageViewTracker->getEventType());

    }
}
