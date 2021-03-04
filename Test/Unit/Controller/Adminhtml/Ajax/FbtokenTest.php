<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Controller\Adminhtml\Ajax;

use FacebookAds\Object\ServerSide\AdsPixelSettings;

class FbtokenTest extends \PHPUnit\Framework\TestCase
{

    protected $fbeHelper;

    protected $context;

    protected $resultJsonFactory;

    protected $fbtoken;

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
        $this->fbtoken = new \Facebook\BusinessExtension\Controller\Adminhtml\Ajax\Fbtoken(
            $this->context,
            $this->resultJsonFactory,
            $this->fbeHelper
        );
    }

    /**
     *
     * @return void
     */
    public function testExecuteForJson()
    {
        $accessToken = 'abcd';
        $this->request->method('getParam')
            ->willReturn($accessToken);
        $this->fbeHelper->method('getConfigValue')
            ->willReturn($accessToken);
        $result = $this->fbtoken->executeForJson();
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($accessToken, $result['accessToken']);
    }

    /**
     *
     * @return void
     */
    public function testExecuteForJsonNoProfiles()
    {
        $accessToken = 'abcd';
        $this->request->method('getParam')
            ->willReturn(null);
        $this->fbeHelper->method('getConfigValue')
            ->willReturn($accessToken);
        $result = $this->fbtoken->executeForJson();
        $this->assertNotNull($result);
        $this->assertFalse($result['success']);
        $this->assertEquals($accessToken, $result['accessToken']);
    }
}
