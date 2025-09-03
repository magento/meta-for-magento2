<?php

namespace Meta\BusinessExtension\Test\Unit\Api\CustomApiKey;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Meta\BusinessExtension\Model\Api\CustomApiKey\ApiKeyService;
use Meta\BusinessExtension\Model\Api\CustomApiKey\KeyGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

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
        $this->apiKeyGenerator = $this->getMockBuilder(KeyGenerator::class)
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

    public function testUpsertApiKey()
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

    public function testGetCustomApiKeyWithNull()
    {
        $this->scopeConfig->method('getValue')
            ->with('meta_extension/general/api_key')
            ->willReturn(null);
        $apiKeyService = new ApiKeyService(
            $this->apiKeyGenerator,
            $this->configWriter,
            $this->scopeConfig,
            $this->logger
        );
        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(['API key does not exist. Generating a new key.'], ['API key has been generated and saved.'])
            ->willReturnSelf();
        $this->configWriter->expects($this->once())->method('save');
        $result = $apiKeyService->getCustomApiKey();
        
        $this->assertIsString($result);
    }

    public function testCustomApiKey()
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
        $result = $apiKeyService->getCustomApiKey();
        $this->configWriter->expects($this->never())->method('save');
        $this->assertEquals($apiKey, $result);
    }
}
