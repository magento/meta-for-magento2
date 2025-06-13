<?php
declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Controller\Adminhtml\ApiKey;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Meta\BusinessExtension\Controller\Adminhtml\ApiKey\Index;
use Meta\BusinessExtension\Model\Api\CustomApiKey\KeyGenerator;

class IndexTest extends TestCase
{
    /**
     * Class setUp function
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->authorization = $this->createMock(AuthorizationInterface::class);
        $this->resultFactory = $this->createMock(ResultFactory::class);
        $this->resultFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->createMock(\Magento\Framework\Controller\Result\Json::class));
        $this->context = $this->createMock(Context::class);
        $this->context->expects($this->once())
            ->method('getAuthorization')
            ->willReturn($this->authorization);
        $this->context->expects($this->once())
            ->method('getResultFactory')
            ->willReturn($this->resultFactory);
        $this->apiKeyGenerator = $this->createMock(KeyGenerator::class);
        $this->configWriter = $this->createMock(WriterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $objectManager = new ObjectManager($this);
        $this->indexMockObj = $objectManager->getObject(
            Index::class,
            [
                'context' => $this->context,
                'apiKeyGenerator' => $this->apiKeyGenerator,
                'configWriter' => $this->configWriter,
                'logger' => $this->logger
            ]
        );
    }

    /**
     * Test the execute method of the Index controller
     * 
     * @return void
     */
    public function testExecute(): void
    {
        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(['API Key Controller was accessed.'], ['API Key Controller was attempted.']);

        $this->authorization->expects($this->once())
            ->method('isAllowed')
            ->with('Meta_BusinessExtension::generate_api_key')
            ->willReturn(true);

        $this->apiKeyGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('test-api-key');

        $this->configWriter->expects($this->once())
            ->method('save')
            ->with('meta_extension/general/api_key', 'test-api-key');

        $result = $this->indexMockObj->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    /**
     * Test the execute method of the Index controller
     * 
     * @return void
     */
    public function testExecuteWithoutAuthorization(): void
    {
        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(['API Key Controller was accessed.'], ['API Key Controller was attempted.']);

        $this->authorization->expects($this->once())
            ->method('isAllowed')
            ->with('Meta_BusinessExtension::generate_api_key')
            ->willReturn(false);

        $result = $this->indexMockObj->execute();
    }
}