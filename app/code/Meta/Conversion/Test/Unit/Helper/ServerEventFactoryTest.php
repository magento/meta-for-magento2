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

namespace Meta\Conversion\Test\Unit\Helper;

use Meta\Conversion\Helper\ServerEventFactory;
use PHPUnit\Framework\TestCase;

class ServerEventFactoryTest extends TestCase
{
    /**
     * Used to reset or change values after running a test
     *
     * @return void
     */
    public function tearDown(): void
    {
    }

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
    }

    public function testNewEventHasId()
    {
        $event = ServerEventFactory::newEvent('ViewContent');
        $this->assertNotNull($event->getEventId());
    }

    public function testNewEventHasProvidedId()
    {
        $eventId = '1234';
        $event = ServerEventFactory::newEvent('ViewContent', $eventId);
        $this->assertEquals($event->getEventId(), $eventId);
    }

    public function testNewEventHasEventTime()
    {
        $event = ServerEventFactory::newEvent('ViewContent');
        $this->assertNotNull($event->getEventTime());
        $this->assertLessThan(1, time() - $event->getEventTime());
    }

    public function testNewEventHasEventName()
    {
        $event =  ServerEventFactory::newEvent('ViewContent');
        $this->assertEquals('ViewContent', $event->getEventName());
    }

    public function testNewEventHasActionSource()
    {
        $event =  ServerEventFactory::newEvent('ViewContent');
        $this->assertEquals('website', $event->getActionSource());
    }

    public function testNewEventHasIpAddressFromPublicIp()
    {
        $_SERVER['HTTP_CLIENT_IP'] = '173.10.20.30';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = null;
        $event =  ServerEventFactory::newEvent('ViewContent');
        $this->assertEquals($event->getUserData()->getClientIpAddress(), '173.10.20.30');
    }

    public function testNewEventHasIpAddressFromIpList()
    {
        $_SERVER['HTTP_CLIENT_IP'] = null;
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '173.10.20.30, 192.168.0.1';
        $event =  ServerEventFactory::newEvent('ViewContent');
        $this->assertEquals($event->getUserData()->getClientIpAddress(), '173.10.20.30');
    }

    public function testNewEventHasNoIpAddressFromPrivateIP()
    {
        $_SERVER['HTTP_CLIENT_IP'] = null;
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.0.1';
        $event =  ServerEventFactory::newEvent('ViewContent');
        $this->assertNull($event->getUserData()->getClientIpAddress());
    }

    public function testNewEventHasUserAgent()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'test-agent';

        $event =  ServerEventFactory::newEvent('ViewContent');

        $this->assertEquals($event->getUserData()->getClientUserAgent(), 'test-agent');
    }

    public function testNewEventHasEventSourceUrlWithHttps()
    {
        $_SERVER['HTTPS'] = 'anyvalue';
        $_SERVER['HTTP_HOST'] = 'www.pikachu.com';
        $_SERVER['REQUEST_URI'] = '/index.php';

        $event = ServerEventFactory::newEvent('ViewContent');

        $this->assertEquals('https://www.pikachu.com/index.php', $event->getEventSourceUrl());
    }

    public function testNewEventHasEventSourceUrlWithHttp()
    {
        $_SERVER['HTTPS'] = '';
        $_SERVER['HTTP_HOST'] = 'www.pikachu.com';
        $_SERVER['REQUEST_URI'] = '/index.php';

        $event = ServerEventFactory::newEvent('ViewContent');

        $this->assertEquals('http://www.pikachu.com/index.php', $event->getEventSourceUrl());
    }

    public function testNewEventHasFbc()
    {
        $_COOKIE['_fbc'] = '_fbc_value';

        $event = ServerEventFactory::newEvent('ViewContent');

        $this->assertEquals('_fbc_value', $event->getUserData()->getFbc());
    }

    public function testNewEventHasFbp()
    {
        $_COOKIE['_fbp'] = '_fbp_value';

        $event = ServerEventFactory::newEvent('ViewContent');

        $this->assertEquals('_fbp_value', $event->getUserData()->getFbp());
    }
}
