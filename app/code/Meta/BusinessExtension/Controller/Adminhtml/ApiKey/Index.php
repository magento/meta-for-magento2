<?php

namespace Meta\BusinessExtension\Controller\Adminhtml\ApiKey;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Meta\BusinessExtension\Model\ApiKeyGenerator;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Psr\Log\LoggerInterface;

class Index extends Action
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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Index constructor
     *
     * @param Action\Context $context
     * @param ApiKeyGenerator $apiKeyGenerator
     * @param WriterInterface $configWriter
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context  $context,
        ApiKeyGenerator $apiKeyGenerator,
        WriterInterface $configWriter,
        LoggerInterface $logger
    ) {
        $this->apiKeyGenerator = $apiKeyGenerator;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Execute the controller action to generate and save the API key
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $this->logger->info('API Key Controller was accessed.');

        if (!$this->_isAllowed()) {
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setHttpResponseCode(403);
        }

        $apiKey = $this->apiKeyGenerator->generate();
        $this->configWriter->save('meta_extension/general/api_key', $apiKey);

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData(['key' => $apiKey]);
        return $resultJson;
    }

    /**
     * Check if the user has the required permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        $this->logger->info('API Key Controller was attempted.');

        return $this->_authorization->isAllowed('Meta_BusinessExtension::generate_api_key');
    }
}
