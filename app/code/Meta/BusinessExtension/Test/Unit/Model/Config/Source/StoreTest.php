<?php
declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Model\Config\Source;

use PHPUnit\Framework\TestCase;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Meta\BusinessExtension\Model\Config\Source\Store;
use Magento\Store\Model\Store as CoreStore;
use Magento\Store\Model\StoreManager;

class StoreTest extends TestCase
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Store
     */
    private $storeMockObj;

    /**
     * Class setup function
     * 
     * @return void
     */
    protected function setup(): void
    {
        parent::setup();
        
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeMockObj = new Store($this->storeManager);
    }

    /**
     * Get Store Validation
     * 
     * @return void
     */
    public function testGetStores(): void
    {
        $storeMock = $this->getMockStore();
        $websiteMock = $this->getMockWebsite();

        $this->storeManager->method('getStores')->willReturn([$storeMock]);
        $this->storeManager->method('getWebsite')->willReturn($websiteMock);

        $result = $this->storeMockObj->getStores();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(1, $result);
        $this->assertEquals('Meta Website -> Ad Publish', $result[1]);
    }

    /**
     * Get Stores with exception handle
     */
    public function testGetStoresWithLocalizedException(): void
    {
        $storeMock = $this->getMockStore();
        $websiteMock = $this->getMockWebsite();

        $this->storeManager->method('getStores')->willReturn([$storeMock]);
        $this->storeManager->method('getWebsite')->willThrowException(new LocalizedException(__('Something went wrong.')));

        $result = $this->storeMockObj->getStores();

        $this->assertEmpty($result);
    }

    /**
     * Returns the mock store
     * 
     * @return $storeMock
     */
    private function getMockStore(): CoreStore
    {
        $storeMock = $this->createMock(CoreStore::class);

        $storeMock->method('isActive')->willReturn(true);
        $storeMock->method('getWebsiteId')->willReturn(1);
        $storeMock->method('getFrontendName')->willReturn('Ad Publish');
        $storeMock->method('getId')->willReturn(1);

        return $storeMock;
    }

    /**
     * Returns the mock website
     * 
     * @return $websiteMock
     */
    private function getMockWebsite(): WebsiteInterface
    {
        $websiteMock = $this->createMock(WebsiteInterface::class);
        $websiteMock->method('getName')->willReturn('Meta Website');

        return $websiteMock;

    }
}