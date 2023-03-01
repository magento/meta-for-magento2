<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Meta\Catalog\Test\Unit\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config;
use Meta\Catalog\Model\Product\Feed\Method\BatchApi;
use Meta\Catalog\Observer\Product\SaveAfter as ProcessProductAfterSaveEventObserver;
use PHPUnit\Framework\TestCase;

class ProcessProductAfterSaveEventObserverTest extends TestCase
{
    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var Config
     */
    private Config $systemConfigMock;

    /**
     * @var ProcessProductAfterSaveEventObserver
     */
    private ProcessProductAfterSaveEventObserver $processProductAfterSaveEventObserver;

    /**
     * @var BatchApi
     */
    private BatchApi $batchApiMock;

    /**
     * @var GraphAPIAdapter
     */
    private GraphAPIAdapter $graphApiAdapterMock;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepositoryMock;

    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->systemConfigMock = $this->createMock(Config::class);
        $this->graphApiAdapterMock = $this->createMock(GraphAPIAdapter::class);
        $this->batchApiMock = $this->createMock(BatchApi::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);

        $this->processProductAfterSaveEventObserver = new ProcessProductAfterSaveEventObserver(
            $this->systemConfigMock,
            $this->fbeHelper,
            $this->batchApiMock,
            $this->graphApiAdapterMock,
            $this->messageManager,
            $this->productRepositoryMock
        );
    }

    public function testExecute(): void
    {
        $observerMock = $this->createMock(Observer::class);
        $eventMock = $this->getMockBuilder(Event::class)->addMethods(['getProduct'])->getMock();
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->addMethods(['getSendToFacebook'])
            ->getMock();
        $eventMock->expects($this->once())->method('getProduct')->willReturn($productMock);

        $productMock->expects($this->once())->method('getId')->willReturn("1234");

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $this->systemConfigMock->method('getStoreManager')->willReturn($storeManager);

        $store = $this->createMock(StoreInterface::class);
        $storeManager->method('getStores')->willReturn([$store]);

        $store->method('getId')->willReturn('1');

        $this->systemConfigMock->method('isActiveExtension')->willReturn(true);
        $this->systemConfigMock->method('isActiveIncrementalProductUpdates')->willReturn(true);

        $this->productRepositoryMock->method('getById')->willReturn($productMock);

        $productMock->method('getSendToFacebook')->willReturn(1);

        $this->systemConfigMock->method('getCatalogId')->willReturn("12345");

        $this->batchApiMock->expects($this->once())
            ->method('buildRequestForIndividualProduct')
            ->with($productMock);
        $this->graphApiAdapterMock->expects($this->atLeastOnce())->method('catalogBatchRequest');
        $this->processProductAfterSaveEventObserver->execute($observerMock);
    }
}
