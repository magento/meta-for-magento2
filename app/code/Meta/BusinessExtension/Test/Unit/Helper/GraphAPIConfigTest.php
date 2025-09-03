<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Meta\BusinessExtension\Helper\GraphAPIConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class GraphAPIConfigTest extends TestCase
{
    /**
     * Setup function
     */
    protected function setUp(): void
    {
        $this->scopeConfigInterface = $this->createMock(ScopeConfigInterface::class);
        $this->context = $this->createMock(Context::class);
        $this->context->expects($this->once())
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfigInterface);

        $objectManager = new ObjectManager($this);
        $this->graphAPIConfigMockObj = $objectManager->getObject(
            GraphAPIConfig::class,
            [
                'context' => $this->context
            ]
        );
    }

    /**
     * Test getGraphBaseURL function
     *
     * @return void
     */
    public function testGetGraphBaseURL(): void
    {
        $this->assertSame('https://graph.facebook.com/', $this->graphAPIConfigMockObj->getGraphBaseURL());
    }

    /**
     * Test getGraphBaseURL function
     *
     * @return void
     */
    public function testGetGraphBaseURLWithOverride(): void
    {
        $this->scopeConfigInterface->expects($this->once())
            ->method('getValue')
            ->willReturn('https://graph.facebook.com/v20.3/');
        $this->assertSame('https://graph.facebook.com/v20.3/', $this->graphAPIConfigMockObj->getGraphBaseURL());
    }
}
