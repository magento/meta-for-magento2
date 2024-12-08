<?php

declare(strict_types=1);

namespace Meta\Conversion\Observer;

use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Cookie\Helper\Cookie as CookieHelper;

/**
 * Set cookie with payload data for event pixel
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Common
{
    /**
     * @var JsonHelper
     */
    private JsonHelper $jsonHelper;

    /**
     * @var CookieMetadataFactory
     */
    private CookieMetadataFactory $cookieMetadataFactory;

    /**
     * @var CookieManagerInterface
     */
    private CookieManagerInterface $cookieManager;

    /**
     * @var CookieHelper
     */
    private CookieHelper $cookieHelper;

    /**
     * Constructor common
     *
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param JsonHelper $jsonHelper
     * @param CookieHelper $cookieHelper
     */
    public function __construct(
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        JsonHelper $jsonHelper,
        CookieHelper $cookieHelper
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->jsonHelper = $jsonHelper;
        $this->cookieHelper = $cookieHelper;
    }

    /**
     * Set data in cookie for meta
     *
     * @param string $cookieName
     * @param array $dataForMetaPixel
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function setCookieForMetaPixel($cookieName, $dataForMetaPixel)
    {
        if ($this->cookieHelper->isCookieRestrictionModeEnabled()
            && $this->cookieHelper->isUserNotAllowSaveCookie()
        ) {
            return;
        }

        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDuration(3600)
            ->setPath('/')
            ->setHttpOnly(false);

        $this->cookieManager->setPublicCookie(
            $cookieName,
            $this->jsonHelper->jsonEncode($dataForMetaPixel),
            $publicCookieMetadata
        );
    }
}
