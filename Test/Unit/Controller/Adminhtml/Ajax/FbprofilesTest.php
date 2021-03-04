<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Controller\Adminhtml\Ajax;

use FacebookAds\Object\ServerSide\AdsPixelSettings;

class FbprofilesTest extends \PHPUnit\Framework\TestCase
{

    protected $fbeHelper;

    protected $context;

    protected $resultJsonFactory;

    protected $fbprofiles;

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
        $this->fbprofiles = new \Facebook\BusinessExtension\Controller\Adminhtml\Ajax\Fbprofiles(
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
        $profiles = '1234';
        $this->request->method('getParam')
            ->willReturn($profiles);
        $this->fbeHelper->method('getConfigValue')
            ->willReturn($profiles);
        $result = $this->fbprofiles->executeForJson();
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($profiles, $result['profiles']);
    }

    /**
     *
     * @return void
     */
    public function testExecuteForJsonNoProfiles()
    {
        $profiles = '1234';
        $this->request->method('getParam')->willReturn(null);
        $this->fbeHelper->method('getConfigValue')
            ->willReturn($profiles);
        $result = $this->fbprofiles->executeForJson();
        $this->assertNotNull($result);
        $this->assertFalse($result['success']);
        $this->assertEquals($profiles, $result['profiles']);
    }
}
