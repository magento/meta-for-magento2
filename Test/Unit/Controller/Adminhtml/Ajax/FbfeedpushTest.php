<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Controller\Adminhtml\Ajax;

use FacebookAds\Object\ServerSide\AdsPixelSettings;

class FbfeedpushTest extends \PHPUnit\Framework\TestCase
{

    protected $fbeHelper;

    protected $context;

    protected $resultJsonFactory;

    protected $fbFeedPush;

    protected $request;

    protected $customerSession;

    protected $_batchApi;

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
        $this->customerSession = $this->createMock(\Magento\Customer\Model\Session::class);
        $this->_batchApi = $this->createMock(\Facebook\BusinessExtension\Model\Product\Feed\Method\BatchApi::class);

        $this->fbFeedPush = new \Facebook\BusinessExtension\Controller\Adminhtml\Ajax\Fbfeedpush(
            $this->context,
            $this->resultJsonFactory,
            $this->fbeHelper,
            $this->_batchApi
        );
    }

    /**
     * Test the case when external biz id already saved
     *
     * @return void
     */
    public function testExternalBizIdExists()
    {
        $this->fbeHelper->method('getConfigValue')->willReturn('bizID');
        $result = $this->fbFeedPush->executeForJson();
        $this->assertFalse($result['success']);
        $this->assertNotNull($result['message']);
        $this->assertEquals('One time feed push is completed at the time of setup', $result['message']);
    }

    /**
     * Test the case when external biz id already saved
     *
     * @return void
     */
    public function testExternalBizIdNotExists()
    {
        $this->fbeHelper->method('getConfigValue')->willReturn(null);
        $this->request->method('getParam')->willReturn('randomStr');
        $this->fbeHelper->method('saveConfig')->willReturn(null);
        $this->_batchApi->method('generateProductRequestData')->willReturn("feed push successfully");
        $result = $this->fbFeedPush->executeForJson();
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['feed_push_response']);
        $this->assertEquals('feed push successfully', $result['feed_push_response']);
    }
}
