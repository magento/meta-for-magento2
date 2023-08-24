<?php

namespace Meta\BusinessExtension\Helper;

use Meta\BusinessExtension\Model\ApiKeyGenerator;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

/**
 * This class handles storage and retrieval for the CUSTOM api token we'll use to authenticate the Meta-specific
 * settings and management APIs we expose as part of the extension. It will not be used for any standard Magento APIs.
 */
class ApiKeyService
{
    /**
     * @var ApiKeyGenerator
     */
    protected $apiKeyGenerator;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ApiKeyGenerator $apiKeyGenerator
     * @param WriterInterface $configWriter
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ApiKeyGenerator      $apiKeyGenerator,
        WriterInterface      $configWriter,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface      $logger
    ) {
        $this->apiKeyGenerator = $apiKeyGenerator;
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Generate and save the API key if it doesn't already exist
     *
     * @return string
     */
    public function upsertApiKey()
    {
        $existingApiKey = $this->scopeConfig->getValue('meta_extension/general/api_key');
        if ($existingApiKey === null) {
            $this->logger->info('API key does not exist. Generating a new key.');
            $apiKey = $this->apiKeyGenerator->generate();
            $this->configWriter->save('meta_extension/general/api_key', $apiKey);
            $this->logger->info('API key has been generated and saved.');
            return $apiKey;
        } else {
            $this->logger->info('API key already exists. No action taken.');
            return $existingApiKey;
        }
    }
}
