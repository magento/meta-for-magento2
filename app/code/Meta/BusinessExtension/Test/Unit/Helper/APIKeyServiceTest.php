<?php

namespace Meta\BusinessExtension\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Meta\BusinessExtension\Helper\ApiKeyService;
use Meta\BusinessExtension\Model\ApiKeyGenerator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class APIKeyServiceTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $apiKeyGenerator;

    /**
     * @var MockObject
     */
    private $configWriter;

    /**
     * @var MockObject
     */
    private $scopeConfig;

    /**
     * @var MockObject
     */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKeyGenerator = $this->getMockBuilder(ApiKeyGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configWriter = $this->getMockBuilder(WriterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetApiKey()
    {
        $apiKey = 'generated-api-key';
        $this->scopeConfig->method('getValue')
            ->with('meta_extension/general/api_key')
            ->willReturn($apiKey);
        $apiKeyService = new ApiKeyService(
            $this->apiKeyGenerator,
            $this->configWriter,
            $this->scopeConfig,
            $this->logger
        );
        $result = $apiKeyService->upsertApiKey();
        $this->configWriter->expects($this->never())->method('save');
        $this->assertEquals($apiKey, $result);
    }
}
