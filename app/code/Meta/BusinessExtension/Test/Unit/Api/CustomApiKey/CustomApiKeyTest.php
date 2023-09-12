<?php

namespace Meta\BusinessExtension\Test\Unit\Api\CustomApiKey;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Meta\BusinessExtension\Api\CustomApiKey\UnauthorizedTokenException;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomApiKeyTest extends TestCase
{
    /**
     * @var MockObject
     */
    private ScopeConfigInterface $scopeConfig;


    /** @var Http */
    private Http $httpRequest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->httpRequest = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testAuthenticateApiKeyFailed()
    {
        $this->expectException(UnauthorizedTokenException::class);

        $apiKey = 'generated-api-key';
        $wrongApiKey = 'wrong-api-key';
        $this->scopeConfig->method('getValue')
            ->with('meta_extension/general/api_key')
            ->willReturn($apiKey);

        // Define the mock behavior
        $this->httpRequest->method('getHeader')
            ->with('Meta-extension-token') // Expect the getHeader method to be called with this argument.
            ->willReturn($wrongApiKey); // Return this value when the above is called.
        $authenticator = new Authenticator(
            $this->scopeConfig,
            $this->httpRequest
        );
        $authenticator->authenticateRequest();
    }

    public function testAuthenticateApiKeySuccess()
    {
        $this->expectNotToPerformAssertions();
        $apiKey = 'generated-api-key';
        $this->scopeConfig->method('getValue')
            ->with('meta_extension/general/api_key')
            ->willReturn($apiKey);
        // Define the mock behavior
        $this->httpRequest->method('getHeader')
            ->with('Meta-extension-token') // Expect the getHeader method to be called with this argument.
            ->willReturn($apiKey); // Return this value when the above is called.
        $authenticator = new Authenticator(
            $this->scopeConfig,
            $this->httpRequest
        );
        $authenticator->authenticateRequest();

    }
}
