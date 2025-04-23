<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Api;

use Meta\BusinessExtension\Model\Api\AdobeCloudConfig;
use PHPUnit\Framework\TestCase;

class AdobeCloudConfigTest extends TestCase
{
    /**
     * @var AdobeCloudConfig
     */
    private $adobeCloudConfig;

    /**
     * Class setup function
     * 
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->adobeCloudConfig = $this->getMockBuilder(AdobeCloudConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Validate if seller is on Cloud
     * 
     * @return void
     */
    public function testIsSellerOnAdobeCloudReturnsTrueWhenEnvVarIsSet(): void
    {
        /** check if the envinorment is non-premise */
        $_ENV['MAGENTO_CLOUD_ENVIRONMENT'] = 'MAGENTO_CLOUD_ENVIRONMENT';
        $this->adobeCloudConfig->method('isSellerOnAdobeCloud')
            ->willReturn(true);

        $this->assertTrue($this->adobeCloudConfig->isSellerOnAdobeCloud());
        unset($_ENV['MAGENTO_CLOUD_ENVIRONMENT']);
    }

    /**
     * Validate if seller is on-premise
     * 
     * @return void
     */
    public function testIsSellerOnAdobeCloudReturnsFalseWhenEnvVarIsNotSet(): void
    {
        /** check if the envinorment is on-premise */
        unset($_ENV['MAGENTO_CLOUD_ENVIRONMENT']);
        $this->adobeCloudConfig->method('isSellerOnAdobeCloud')
            ->willReturn(false);
        
        $this->assertFalse($this->adobeCloudConfig->isSellerOnAdobeCloud());
    }
}
