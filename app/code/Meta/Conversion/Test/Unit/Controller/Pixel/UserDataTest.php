<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Controller\Pixel;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Controller\Pixel\UserData;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Meta\BusinessExtension\Helper\FBEHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Meta\Conversion\Helper\AAMFieldsExtractorHelper;
use Magento\Customer\Model\Customer;
use function PHPUnit\Framework\assertEquals;

class UserDataTest extends TestCase
{
    private $jsonFactoryMock;
    private $jsonMock;
    private $fbeHelperMock;
    private $customerSessionMock;
    private $aamFieldsExtractorHelperMock;
    private $subject;

    private $customerMock;

    public function setUp(): void
    {
        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();


        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fbeHelperMock = $this->getMockBuilder(FBEHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->aamFieldsExtractorHelperMock = $this->getMockBuilder(AAMFieldsExtractorHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(UserData::class, [
            'jsonFactory' => $this->jsonFactoryMock,
            'fbeHelper' => $this->fbeHelperMock,
            'customerSession' => $this->customerSessionMock,
            'aamFieldsExtractorHelper' => $this->aamFieldsExtractorHelperMock
        ]);
    }

    public function testGetCustomer()
    {
        $this->customerSessionMock->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->customerSessionMock->expects($this->once())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->assertEquals($this->customerMock, $this->subject->getCustomer());
    }

    public function testGetCustomerNotLoggedIn()
    {
        $this->customerSessionMock->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->assertNull($this->subject->getCustomer());
    }

    public function testExecute()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'age' => 32
        ];
        $response = [
            'user_data' => $userData,
            'success' => true
        ];

        $this->customerSessionMock->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->customerSessionMock->expects($this->once())
            ->method('getCustomer')
            ->willReturn($this->customerMock);
        $this->aamFieldsExtractorHelperMock->expects($this->once())
            ->method('getNormalizedUserData')
            ->with($this->customerMock)
            ->willReturn($userData);
        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->once())
            ->method('setData')
            ->with($response)
            ->willReturnSelf();

        $this->assertEquals($this->jsonMock, $this->subject->execute());
    }
}
