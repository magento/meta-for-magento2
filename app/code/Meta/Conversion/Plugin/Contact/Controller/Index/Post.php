<?php
declare(strict_types=1);

namespace Meta\Conversion\Plugin\Contact\Controller\Index;

use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Meta\Conversion\Observer\Common;
use Magento\Contact\Controller\Index\Post as PostController;

class Post
{
    /**
     * @var Common
     */
    private Common $common;

    /**
     * Constructor after Contact Post
     *
     * @param Common $common
     */
    public function __construct(
        Common $common
    ) {
        $this->common = $common;
    }

    /**
     * Plugin for execute for adding payload in cookie
     *
     * @param \Magento\Contact\Controller\Index\Post $subject
     * @param Redirect $result
     * @return Redirect
     * @throws InputException
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(PostController $subject, Redirect $result)
    {
        $contactData = [
            'content_type' => "contact"
        ];
        $this->common->setCookieForMetaPixel('event_contact', $contactData);
        return $result;
    }
}
