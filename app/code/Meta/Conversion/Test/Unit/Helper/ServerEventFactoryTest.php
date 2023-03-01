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

use Magento\Framework\App\Request\Http;
use Meta\Conversion\Helper\ServerEventFactory;
use PHPUnit\Framework\TestCase;

class ServerEventFactoryTest extends TestCase
{
    /**
     * @var ServerEventFactory
     */
    private ServerEventFactory $serverEventFactory;

    /**
     * @var Http
     */
    private Http $httpRequestMock;

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->httpRequestMock = $this->createMock(Http::class);
        $this->serverEventFactory = new ServerEventFactory($this->httpRequestMock, []);
    }

    public function testNewEventHasId(): void
    {
        $this->httpRequestMock->method('getServerValue')->willReturn([]);
        $event = $this->serverEventFactory->newEvent('ViewContent');
        $this->assertNotNull($event->getEventId());
    }

    public function testNewEventHasProvidedId(): void
    {
        $this->httpRequestMock->method('getServerValue')->willReturn([]);
        $eventId = '1234';
        $event = $this->serverEventFactory->newEvent('ViewContent', $eventId);
        $this->assertEquals($event->getEventId(), $eventId);
    }

    public function testNewEventHasEventTime(): void
    {
        $this->httpRequestMock->method('getServerValue')->willReturn([]);
        $event = $this->serverEventFactory->newEvent('ViewContent');
        $this->assertNotNull($event->getEventTime());
        $this->assertLessThan(1, time() - $event->getEventTime());
    }

    public function testNewEventHasEventName()
    {
        $this->httpRequestMock->method('getServerValue')->willReturn([]);
        $event = $this->serverEventFactory->newEvent('ViewContent');
        $this->assertEquals('ViewContent', $event->getEventName());
    }

    public function testNewEventHasActionSource()
    {
        $this->httpRequestMock->method('getServerValue')->willReturn([]);
        $event = $this->serverEventFactory->newEvent('ViewContent');
        $this->assertEquals('website', $event->getActionSource());
    }

    public function testNewEventHasIpAddressFromPublicIp()
    {
        $this->httpRequestMock->method('getServerValue')->willReturn([
            'HTTP_CLIENT_IP' => '173.10.20.30',
            'HTTP_X_FORWARDED_FOR' => null,
        ]);
        $event = $this->serverEventFactory->newEvent('ViewContent');
        $this->assertEquals($event->getUserData()->getClientIpAddress(), '173.10.20.30');
    }

    public function testNewEventHasIpAddressFromIpList()
    {
        $this->httpRequestMock->method('getServerValue')->willReturn([
            'HTTP_CLIENT_IP' => null,
            'HTTP_X_FORWARDED_FOR' => '173.10.20.30, 192.168.0.1',
        ]);
        $event = $this->serverEventFactory->newEvent('ViewContent');
        $this->assertEquals($event->getUserData()->getClientIpAddress(), '173.10.20.30');
    }

    public function testNewEventHasNoIpAddressFromPrivateIP()
    {
        $this->httpRequestMock->method('getServerValue')->willReturn([
            'HTTP_CLIENT_IP' => null,
            'HTTP_X_FORWARDED_FOR' => '192.168.0.1',
        ]);
        $event = $this->serverEventFactory->newEvent('ViewContent');
        $this->assertNull($event->getUserData()->getClientIpAddress());
    }

    public function testNewEventHasUserAgent()
    {
        $this->httpRequestMock->method('getServerValue')->willReturn([]);
        // Forced to set value on super global due to meta code accessing it directly
        $_SERVER['HTTP_USER_AGENT'] = 'test-agent';

        $event = $this->serverEventFactory->newEvent('ViewContent');

        $this->assertEquals($event->getUserData()->getClientUserAgent(), 'test-agent');
    }

    public function testNewEventHasEventSourceUrlWithHttps()
    {
        $this->httpRequestMock->method('getServerValue')->willReturn([]);
        // Forced to set value on super global due to meta code accessing it directly
        $_SERVER['HTTPS'] = 'anyvalue';
        $_SERVER['HTTP_HOST'] = 'www.pikachu.com';
        $_SERVER['REQUEST_URI'] = '/index.php';

        $event = $this->serverEventFactory->newEvent('ViewContent');

        $this->assertEquals('https://www.pikachu.com/index.php', $event->getEventSourceUrl());
    }

    public function testNewEventHasEventSourceUrlWithHttp()
    {
        $this->httpRequestMock->method('getServerValue')->willReturn([]);
        // Forced to set value on super global due to meta code accessing it directly
        $_SERVER['HTTPS'] = '';
        $_SERVER['HTTP_HOST'] = 'www.pikachu.com';
        $_SERVER['REQUEST_URI'] = '/index.php';

        $event = $this->serverEventFactory->newEvent('ViewContent');

        $this->assertEquals('http://www.pikachu.com/index.php', $event->getEventSourceUrl());
    }

    public function testNewEventHasFbc()
    {
        $this->httpRequestMock->method('getServerValue')->willReturn([]);
        // Forced to set value on super global due to meta code accessing it directly
        $_COOKIE['_fbc'] = '_fbc_value';

        $event = $this->serverEventFactory->newEvent('ViewContent');

        $this->assertEquals('_fbc_value', $event->getUserData()->getFbc());
    }

    public function testNewEventHasFbp()
    {
        $this->httpRequestMock->method('getServerValue')->willReturn([]);
        // Forced to set value on super global due to meta code accessing it directly
        $_COOKIE['_fbp'] = '_fbp_value';

        $event = $this->serverEventFactory->newEvent('ViewContent');

        $this->assertEquals('_fbp_value', $event->getUserData()->getFbp());
    }
}
