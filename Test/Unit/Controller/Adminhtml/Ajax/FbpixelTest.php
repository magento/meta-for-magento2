<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Controller\Adminhtml\Ajax;

use FacebookAds\Object\ServerSide\AdsPixelSettings;

class FbpixelTest extends \PHPUnit\Framework\TestCase
{

    protected $fbeHelper;

    protected $context;

    protected $resultJsonFactory;

    protected $fbPixelTest;

    protected $request;

    /**
     * Used to reset or change values after running a test
     *
     * @return void
     */
    public function tearDown()
    {
    }

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp()
    {
        $this->context = $this->createMock(\Magento\Backend\App\Action\Context::class);
        $this->resultJsonFactory = $this->createMock(\Magento\Framework\Controller\Result\JsonFactory::class);
        $this->fbeHelper = $this->createMock(\Facebook\BusinessExtension\Helper\FBEHelper::class);
        $this->request = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->context->method('getRequest')->willReturn($this->request);
        $this->fbPixelTest = new \Facebook\BusinessExtension\Controller\Adminhtml\Ajax\Fbpixel(
            $this->context,
            $this->resultJsonFactory,
            $this->fbeHelper
        );
    }

    /**
     *
     * @return void
     */
    public function testExecuteForJsonNoPixel()
    {
        $pixelId = '1234';
        $this->request->method('getParam')
            ->willReturn(null);
        $this->fbeHelper->method('getConfigValue')
            ->willReturn($pixelId);
        $result = $this->fbPixelTest->executeForJson();
        $this->assertNotNull($result);
        $this->assertFalse($result['success']);
        $this->assertEquals($pixelId, $result['pixelId']);
    }
}
