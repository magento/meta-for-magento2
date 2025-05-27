<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\BusinessExtension\Plugin\LoggingActionPlugin;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Throwable;
use PHPUnit\Framework\TestCase;

class LoggingActionPluginTest extends TestCase
{
    /**
     * @var LoggingActionPlugin
     */
    private $loggingActionPluginMockObj;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * Class setup function
     * 
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->storeRepository = $this->createMock(StoreRepositoryInterface::class);
        
        $objectManager = new ObjectManager($this);
        $this->loggingActionPluginMockObj = $objectManager->getObject(
            LoggingActionPlugin::class,
            [
                'fbeHelper' => $this->fbeHelper,
                'request' => $this->request,
                'storeRepository' => $this->storeRepository
            ]
        );
    }

    /**
     * Validate if the logging plugin base is instantiated correctly
     * 
     * @return void
     */
    public function testLoggingPluginBaseInstantiation(): void
    {
        $this->assertInstanceOf(LoggingActionPlugin::class, $this->loggingActionPluginMockObj);
    }
}