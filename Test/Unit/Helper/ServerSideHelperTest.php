<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Helper;

use Facebook\BusinessExtension\Helper\AAMFieldsExtractorHelper;
use Facebook\BusinessExtension\Helper\FBEHelper;
use \Facebook\BusinessExtension\Helper\ServerEventFactory;
use Facebook\BusinessExtension\Helper\ServerSideHelper;
use PHPUnit\Framework\TestCase;

class ServerSideHelperTest extends TestCase
{

    protected $fbeHelper;

    protected $serverSideHelper;

    protected $aamFieldsExtractorHelper;

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
        $this->fbeHelper = $this->createMock(FBEHelper::class);
        $this->aamFieldsExtractorHelper =
        $this->createMock(AAMFieldsExtractorHelper::class);
        $this->serverSideHelper =
        new ServerSideHelper($this->fbeHelper, $this->aamFieldsExtractorHelper);
        $this->fbeHelper->method('getAccessToken')->willReturn('abc');
        $this->fbeHelper->method('getPixelID')->willReturn('123');
    }

    public function testEventAddedToTrackedEvents()
    {
        $event = ServerEventFactory::createEvent('ViewContent', []);
        $this->aamFieldsExtractorHelper->method('setUserData')->willReturn($event);
        $this->serverSideHelper->sendEvent($event);
        $this->assertEquals(1, count($this->serverSideHelper->getTrackedEvents()));
        $event = $this->serverSideHelper->getTrackedEvents()[0];
        $this->assertEquals('ViewContent', $event->getEventName());
    }
}
