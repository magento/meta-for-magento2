<?php

namespace Meta\BusinessExtension\Test\Unit\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Api\StoreRepositoryInterface;
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

        $requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiKeyService = $this->getMockBuilder(ApiKeyService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $commerceExtensionHelper = $this->getMockBuilder(CommerceExtensionHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiKeyService->method('upsertApiKey')
            ->willReturn($apiKey);

        $adobeCloudConfigInterface = $this->getMockBuilder(AdobeCloudConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $setup = new Setup(
            $context,
            $requestInterface,
            $fbeHelper,
            $systemConfig,
            $storeRepo,
            $commerceExtensionHelper,
            $apiKeyService,
            $adobeCloudConfigInterface,
            []
        );

        $this->assertEquals($apiKey, $setup->upsertApiKey());
    }
}
