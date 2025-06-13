<?php

namespace Meta\BusinessExtension\Test\Unit\Logger;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Meta\BusinessExtension\Logger\Logger;
use Meta\BusinessExtension\Logger\Handler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Monolog\Logger as MonoLogger;

class LoggerTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|MockObject
     */
    private $objectManagerMock;

    /**
     * @var PublisherInterface|MockObject
     */
    private $publisherMock;

    /**
     * @var Logger
     */
    private $loggerMockObj;

    /**
     * Class setUp function
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $this->publisherMock = $this->createMock(PublisherInterface::class);
        $this->handler = $this->createMock(Handler::class);
        $this->handler->expects($this->once())
            ->method('setPublisher')
            ->with($this->publisherMock);
        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with(Handler::class)
            ->willReturn($this->handler);
        $objectManager = new ObjectManager($this);

        $this->loggerMockObj = $objectManager->getObject(
            Logger::class,
            [
                'objectManager' => $this->objectManagerMock,
                'publisher' => $this->publisherMock
            ]
        );

    }

    public function testConstructorInitializesHandlerAndSetsPublisher()
    {
        $this->assertEquals(
            'FBE',
            $this->loggerMockObj->getName()
        );
    }
}