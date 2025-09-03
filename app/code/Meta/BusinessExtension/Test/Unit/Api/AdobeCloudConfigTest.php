<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Api;

use Meta\BusinessExtension\Model\Api\AdobeCloudConfig;
use Meta\BusinessExtension\Api\AdobeCloudConfigInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

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
        $objectManager = new ObjectManager($this);
        
        $this->adobeCloudConfig = $objectManager->getObject(
            AdobeCloudConfig::class
        );
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
        
        $this->assertFalse($this->adobeCloudConfig->isSellerOnAdobeCloud());
    }

    /**
     * Validate if seller is on-premise
     *
     * @return void
     */
    public function testGetCommercePartnerSellerPlatformType(): void
    {
        /** check if the envinorment is on-premise */
        $_ENV['MAGENTO_CLOUD_ENVIRONMENT'] = 'MAGENTO_CLOUD_ENVIRONMENT';
        
        $this->assertSame(AdobeCloudConfigInterface::ADOBE_COMMERCE_CLOUD, $this->adobeCloudConfig->getCommercePartnerSellerPlatformType());
    }

    /**
     * Validate if seller is on-premise
     *
     * @return void
     */
    public function testGetCommercePartnerSellerPlatformTypeIsNotOnCloud(): void
    {
        /** check if the envinorment is on-premise */
        unset($_ENV['MAGENTO_CLOUD_ENVIRONMENT']);
        
        $this->assertSame(AdobeCloudConfigInterface::MAGENTO_OPEN_SOURCE, $this->adobeCloudConfig->getCommercePartnerSellerPlatformType());
    }
}
