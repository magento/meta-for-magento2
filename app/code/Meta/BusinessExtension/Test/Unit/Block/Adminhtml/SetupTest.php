<?php

namespace Meta\BusinessExtension\Test\Unit\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Api\AdobeCloudConfigInterface;
use Meta\BusinessExtension\Block\Adminhtml\Setup;
use Meta\BusinessExtension\Helper\CommerceExtensionHelper;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\Api\CustomApiKey\ApiKeyService;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use PHPUnit\Framework\TestCase;

class SetupTest extends TestCase
{
    public function testGetApiKey()
    {
        $apiKey = 'sample-api-key';

        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fbeHelper = $this->getMockBuilder(FBEHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $systemConfig = $this->getMockBuilder(SystemConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeRepo = $this->getMockBuilder(StoreRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiKeyService = $this->getMockBuilder(ApiKeyService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $commerceExtensionHelper = $this->getMockBuilder(CommerceExtensionHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $adobeCloudConfigInterface = $this->getMockBuilder(AdobeCloudConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiKeyService->method('upsertApiKey')
            ->willReturn($apiKey);

        $setup = new Setup(
            $context,
            $requestInterface,
            $fbeHelper,
            $systemConfig,
            $storeRepo,
            $storeManager,
            $commerceExtensionHelper,
            $apiKeyService,
            $adobeCloudConfigInterface,
            $scopeConfig,
            []
        );

        $this->assertEquals($apiKey, $setup->upsertApiKey());
    }
}
