<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Api;

use PHPUnit\Framework\TestCase;
use Meta\BusinessExtension\Model\Api\CoreConfig;

class CoreConfigTest extends TestCase
{
    /**
     * @var CoreConfig
     */
    private $metaCoreConfig;

    /**
     * Class setup function
     * 
     * @return void
     */
    protected function setup(): void
    {
        parent::setup();

        $this->metaCoreConfig = $this->getMockBuilder(CoreConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Validate if the external business ID is empty
     * 
     * @return void
     */
    public function testGetExternalBusinessId(): void
    {
        $this->metaCoreConfig->method('getExternalBusinessId')
            ->willReturn('');
        
        $this->assertEmpty($this->metaCoreConfig->getExternalBusinessId());
    }

    /**
     * Validate if the external business ID is set
     * 
     * @return void
     */
    public function testSetExternalBusinessId(): void
    {
        $businessId = 'ZmJfaWRfMQ==';
        $this->metaCoreConfig->setExternalBusinessId($businessId);
        $this->metaCoreConfig->method('getExternalBusinessId')
            ->willReturn($businessId);
        
        $this->assertSame($businessId, $this->metaCoreConfig->getExternalBusinessId());
    }

    /**
     * Validate if the order sync is enabled
     * 
     * @return void
     */
    public function testSetIsOrderSyncEnabled(): void
    {
        $isOrderSyncEnabled = true;
        $this->metaCoreConfig->setIsOrderSyncEnabled($isOrderSyncEnabled);

        $this->metaCoreConfig->method('isOrderSyncEnabled')
            ->willReturn($isOrderSyncEnabled);
        
        $this->assertSame($isOrderSyncEnabled, $this->metaCoreConfig->isOrderSyncEnabled());
    }

    /**
     * Validate if the catalog sync is enabled
     * 
     * @return void
     */
    public function testSetIsCatalogSyncEnabled(): void
    {
        $isCatalogSyncEnabled = true;
        $this->metaCoreConfig->setIsCatalogSyncEnabled($isCatalogSyncEnabled);

        $this->metaCoreConfig->method('isCatalogSyncEnabled')
            ->willReturn($isCatalogSyncEnabled);
        
        $this->assertSame($isCatalogSyncEnabled, $this->metaCoreConfig->isCatalogSyncEnabled());
    }

    /**
     * Validate if the Promotion sync is enabled
     * 
     * @return void
     */
    public function testSetIsPromotionsSyncEnabled(): void
    {
        $isPromotionsSyncEnabled = true;
        $this->metaCoreConfig->setIsPromotionsSyncEnabled($isPromotionsSyncEnabled);

        $this->metaCoreConfig->method('isPromotionsSyncEnabled')
            ->willReturn($isPromotionsSyncEnabled);
        
        $this->assertSame($isPromotionsSyncEnabled, $this->metaCoreConfig->isPromotionsSyncEnabled());
    }

    /**
     * Validate if the extension is active
     * 
     * @return void
     */
    public function testSetIsActiveExtension(): void
    {
        $isActiveExtension = true;
        $this->metaCoreConfig->setIsActiveExtension($isActiveExtension);

        $this->metaCoreConfig->method('isActiveExtension')
            ->willReturn($isActiveExtension);
        
        $this->assertSame($isActiveExtension, $this->metaCoreConfig->isActiveExtension());
    }

    /**
     * Validate if the product identifier attribute is set
     * 
     * @return void
     */
    public function testSetProductIdentifierAttr(): void
    {
        $productIdentifierAttr = 'sku';
        $this->metaCoreConfig->setProductIdentifierAttr($productIdentifierAttr);

        $this->metaCoreConfig->method('getProductIdentifierAttr')
            ->willReturn($productIdentifierAttr);
        
        $this->assertSame($productIdentifierAttr, $this->metaCoreConfig->getProductIdentifierAttr());
    }

    /**
     * Validate if the product stock threashold is set
     * 
     * @return void
     */
    public function testSetOutOfStockThreshold(): void
    {
        $outOfStockThreshold = '10';
        $this->metaCoreConfig->setOutOfStockThreshold($outOfStockThreshold);

        $this->metaCoreConfig->method('getOutOfStockThreshold')
            ->willReturn($outOfStockThreshold);
        
        $this->assertSame($outOfStockThreshold, $this->metaCoreConfig->getOutOfStockThreshold());
    }

    /**
     * Validate if the feed ID is set
     * 
     * @return void
     */
    public function testSetFeedId(): void
    {
        $feedId = 'feed-id';
        $this->metaCoreConfig->setFeedId($feedId);

        $this->metaCoreConfig->method('getFeedId')
            ->willReturn($feedId);
        
        $this->assertSame($feedId, $this->metaCoreConfig->getFeedId());
    }

    /**
     * Validate if the installed meta extension version is set
     * 
     * @return void
     */
    public function testSetInstalledMetaExtensionVersion(): void
    {
        $version = '1.0.0';
        $this->metaCoreConfig->setInstalledMetaExtensionVersion($version);

        $this->metaCoreConfig->method('getInstalledMetaExtensionVersion')
            ->willReturn($version);
        
        $this->assertSame($version, $this->metaCoreConfig->getInstalledMetaExtensionVersion());
    }

    /**
     * Validate if the Graph API version is set
     * 
     * @return void
     */
    public function testSetGraphApiVersion(): void
    {
        $graphApiVersion = 'v1.0';
        $this->metaCoreConfig->setGraphApiVersion($graphApiVersion);

        $this->metaCoreConfig->method('getGraphApiVersion')
            ->willReturn($graphApiVersion);
        
        $this->assertSame($graphApiVersion, $this->metaCoreConfig->getGraphApiVersion());
    }

    /**
     * Validate if the Graph API version is set
     * 
     * @return void
     */
    public function testSetMagentoVersion(): void
    {
        $magentoVersion = '2.4.5';
        $this->metaCoreConfig->setMagentoVersion($magentoVersion);

        $this->metaCoreConfig->method('getMagentoVersion')
            ->willReturn($magentoVersion);
        
        $this->assertSame($magentoVersion, $this->metaCoreConfig->getMagentoVersion());
    }
}
