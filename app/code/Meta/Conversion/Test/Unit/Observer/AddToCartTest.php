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

namespace Meta\Conversion\Test\Unit\Observer;

use Meta\Conversion\Observer\AddToCart;
use Meta\Conversion\Helper\ServerSideHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Event\Observer;
use Magento\Catalog\Model\Product;

class AddToCartTest extends CommonTest
{

    /**
     * @var ServerSideHelper
     */
    private $serverSideHelper;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var ObserverInterface
     */
    private $observer;

    /**
     * @var AddToCart
     */
    private $addToCartObserver;

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->serverSideHelper = $this->getMockBuilder(ServerSideHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->escaper = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->observer = $this->getMockBuilder(Observer::class)
            ->onlyMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->addToCartObserver = $this->objectManager->getObject(AddToCart::class, [
            'fbeHelper' => $this->fbeHelper,
            'magentoDataHelper' => $this->magentoDataHelper,
            'serverSideHelper' => $this->serverSideHelper,
            'request' => $this->request,
            'escaper' => $this->escaper
        ]);
    }

    /**
     * Test execute methood
     *
     * @return void
     */
    public function testExecute()
    {
        $eventId = '12ghjs-34vcv1-dfff3v-43kj97';
        $productId = 12;
        $productName = 'Test Product';
        $currency = 'USD';
        $value = 100.00;
        $contentType = 'product';
        $contentIds = 'test-product';
        $contentCategory = 'Test Category';

        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observer->expects($this->once())
            ->method('getData')
            ->with('eventId')
            ->willReturn($eventId);

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('product_id', null)
            ->willReturn($productId);

        $this->magentoDataHelper->expects($this->once())
            ->method('getProductById')
            ->with($productId)
            ->willReturn($productMock);

        $productMock->expects($this->once())
            ->method('getId')
            ->willReturn($productId);

        $this->magentoDataHelper->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);

        $this->magentoDataHelper->expects($this->once())
            ->method('getValueForProduct')
            ->with($productMock)
            ->willReturn($value);

        $this->magentoDataHelper->expects($this->once())
            ->method('getContentType')
            ->with($productMock)
            ->willReturn($contentType);

        $this->magentoDataHelper->expects($this->once())
            ->method('getContentId')
            ->with($productMock)
            ->willReturn($contentIds);

        $this->magentoDataHelper->expects($this->once())
            ->method('getCategoriesForProduct')
            ->with($productMock)
            ->willReturn($contentCategory);

        $productMock->expects($this->once())
            ->method('getName')
            ->willReturn($productName);

        $this->escaper->expects($this->once())
            ->method('escapeUrl')
            ->with($productName)
            ->willReturn($productName);

        $this->addToCartObserver->execute($this->observer);
    }
}
