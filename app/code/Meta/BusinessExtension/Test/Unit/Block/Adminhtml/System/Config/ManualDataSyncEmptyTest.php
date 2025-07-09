<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Block\Adminhtml\System\Config;

use PHPUnit\Framework\TestCase;
use Meta\BusinessExtension\Block\Adminhtml\System\Config\ManualDataSyncEmpty;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Backend\Block\Template\Context;

class ManualDataSyncEmptyTest extends TestCase
{
    /**
     * Class setUp function
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(RequestInterface::class);
        $context = $this->createMock(Context::class);
        $context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $objectManager = new ObjectManager($this);
        $this->manualDataSyncEmptyMockObj = $objectManager->getObject(
            ManualDataSyncEmpty::class,
            [
                'context' => $context
            ]
        );
    }

    /**
     * Test getStoreId function
     * 
     * @return void
     */
    public function testGetStoreId(): void
    {
        $storeId = 123;
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn($storeId);

        $this->assertEquals($this->manualDataSyncEmptyMockObj->getStoreId(), $storeId);
    }
}