<?php
declare(strict_types=1);

namespace Meta\Conversion\Test\Unit\Plugin\Contact\Controller\Index;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\Conversion\Plugin\Contact\Controller\Index\Post;
use Magento\Contact\Controller\Index\Post as PostController;
use Magento\Framework\Controller\Result\Redirect;
use Meta\Conversion\Observer\Common;

class PostTest extends TestCase
{
    private $commonMock;
    private $subject;

    public function setUp(): void
    {
        $this->commonMock = $this->getMockBuilder(Common::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(Post::class, ['common' => $this->commonMock]);
    }

    public function testAfterExecute()
    {
        $contactData = [
            'content_type' => "contact"
        ];

        $postControllerMock = $this->getMockBuilder(PostController::class)
            ->disableOriginalConstructor()
            ->getMock();
        $redirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->commonMock->expects($this->once())
            ->method('setCookieForMetaPixel')
            ->with('event_contact', $contactData);

        $this->assertEquals($redirectMock, $this->subject->afterExecute($postControllerMock, $redirectMock));
    }
}
