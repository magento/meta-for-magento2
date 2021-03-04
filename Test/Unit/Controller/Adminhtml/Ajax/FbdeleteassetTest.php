<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Controller\Adminhtml\Ajax;

use FacebookAds\Object\ServerSide\AdsPixelSettings;

class FbdeleteassetTest extends \PHPUnit\Framework\TestCase
{

    protected $fbeHelper;

    protected $context;

    protected $resultJsonFactory;

    protected $fbdeleteasset;

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
        $this->fbdeleteasset = new \Facebook\BusinessExtension\Controller\Adminhtml\Ajax\Fbdeleteasset(
            $this->context,
            $this->resultJsonFactory,
            $this->fbeHelper
        );
    }

    /**
     *
     * @return void
     */
    public function testExecuteForJsonNull()
    {
        $this->fbeHelper->method('deleteConfigKeys')
            ->willReturn(null);
        $result = $this->fbdeleteasset->executeForJson();
        $this->assertNull($result);
    }

    /**
     *
     * @return void
     */
    public function testExecuteForJsonNotNull()
    {
        $expected = [
            'success' => true,
            'message' => 'dummy',
        ];
        $this->fbeHelper->method('deleteConfigKeys')
            ->willReturn($expected);
        $result = $this->fbdeleteasset->executeForJson();
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('dummy', $result['message']);
    }
}
