<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Logger;

use Magento\Framework\MessageQueue\PublisherInterface;
use Meta\BusinessExtension\Logger\Handler;
use Magento\Framework\Logger\Handler\Base;
use Magento\Framework\Filesystem\DriverInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class HandlerTest extends TestCase
{
    /**
     * @var Handler
     */
    private $handler;

    /**
     * @var PublisherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $publisherMock;

    protected function setUp(): void
    {
        $filesystem = $this->getMockBuilder(DriverInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->handlerMockObj = $objectManager->getObject(
            Handler::class,
            [
                'filesystem' => $filesystem
            ]
        );
    }

    public function testSetPublisherWithMock(): void
    {
        $this->publisherMock = $this->createMock(PublisherInterface::class);
        $this->handlerMockObj->setPublisher($this->publisherMock);
        $this->assertInstanceOf(Handler::class, $this->handlerMockObj);
    }

    /**
     * Test write function
     *
     * @return void
     */
    public function testWriteWithMetaLogImmediately(): void
    {
        $this->publisherMock = $this->createMock(PublisherInterface::class);
        $this->handlerMockObj->setPublisher($this->publisherMock);

        $record = [
            'message' => 'Test log message',
            'context' => [
                'log_type' => FBEHelper::PERSIST_META_LOG_IMMEDIATELY,
            ],
            'level' => 1,
            'formatted' => 'Test log message',
            'level_name' => 'DEBUG',
        ];

        $reflection = new \ReflectionClass(Handler::class);
        $handlerMockObj = $reflection->getMethod('write');
        $handlerMockObj->setAccessible(true);

        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->with('persist.meta.log.immediately', json_encode($record['context']));

        $handlerMockObj->invoke($this->handlerMockObj, $record);
    }

    /**
     * Test write function
     *
     * @return void
     */
    public function testWriteWithMetaLogTelemetry(): void
    {
        $this->publisherMock = $this->createMock(PublisherInterface::class);
        $this->handlerMockObj->setPublisher($this->publisherMock);

        $record = [
            'message' => 'Test log message',
            'context' => [
                'log_type' => FBEHelper::PERSIST_META_TELEMETRY_LOGS,
            ],
            'level' => 1,
            'formatted' => 'Test log message',
            'level_name' => 'DEBUG',
        ];

        $reflection = new \ReflectionClass(Handler::class);
        $handlerMockObj = $reflection->getMethod('write');
        $handlerMockObj->setAccessible(true);

        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->with('persist.meta.telemetry.logs', json_encode($record['context']));

        $handlerMockObj->invoke($this->handlerMockObj, $record);
    }
}
