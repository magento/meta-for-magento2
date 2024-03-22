<?php

namespace Meta\BusinessExtension\Test\Unit\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Meta\BusinessExtension\Block\Adminhtml\Setup;
use Meta\BusinessExtension\Helper\CommerceExtensionHelper;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\Api\CustomApiKey\ApiKeyService;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SetupTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $context;

    /**
     * @var MockObject
     */
    private $fbeHelper;

    /**
     * @var MockObject
     */
    private $commerceExtensionHelper;

    /**
     * @var MockObject
     */
    private $systemConfig;

    /**
     * @var MockObject
     */
    private $requestInterface;

    /**
     * @var MockObject
     */
    private $storeRepo;

    /**
     * @var MockObject
     */
    private $websiteCollectionFactory;

    /**
     * @var MockObject
     */
    private $apiKeyService;

    public function testGetApiKey()
    {
        $apiKey = 'sample-api-key';

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fbeHelper = $this->getMockBuilder(FBEHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->systemConfig = $this->getMockBuilder(SystemConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeRepo = $this->getMockBuilder(StoreRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteCollectionFactory = $this->getMockBuilder(WebsiteCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->apiKeyService = $this->getMockBuilder(ApiKeyService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->commerceExtensionHelper = $this->getMockBuilder(CommerceExtensionHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->apiKeyService->method('upsertApiKey')
            ->willReturn($apiKey);

        $setup = new Setup(
            $this->context,
            $this->requestInterface,
            $this->fbeHelper,
            $this->systemConfig,
            $this->storeRepo,
            $this->websiteCollectionFactory,
            $this->commerceExtensionHelper,
            $this->apiKeyService,
            []
        );

        $this->assertEquals($apiKey, $setup->upsertApiKey());
    }
}
