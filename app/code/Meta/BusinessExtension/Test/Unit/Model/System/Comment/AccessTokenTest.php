<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Model\System\Comment;

use PHPUnit\Framework\TestCase;
use Meta\BusinessExtension\Model\System\Comment\AccessToken;

class AccessTokenTest extends TestCase
{
    /**
     * @var AccessToken
     */
    private $accessTokenMockObj;

    /**
     * Class setup function
     *
     * @return void
     */
    public function setup(): void
    {
        parent::setup();

        $this->accessTokenMockObj = $this->getMockBuilder(AccessToken::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Test the getCommentText function return content
     *
     * @return void
     */
    public function testGetCommentText(): void
    {
        $elementValue = 'meta_access_token';

        $this->accessTokenMockObj->method('getCommentText')
            ->with($elementValue)
            ->willReturn($this->getReturnValue($elementValue));
        
        $this->assertEquals($this->getReturnValue($elementValue), $this->accessTokenMockObj->getCommentText($elementValue));
    }

    /**
     * Test the getCommentText function return content with empty param
     *
     * @return void
     */
    public function testGetCommentTextEmpty(): void
    {
        $elementValue = '';

        $this->accessTokenMockObj->method('getCommentText')
            ->with($elementValue)
            ->willReturn('');
        
        $this->assertEquals($elementValue, '');
    }

    /**
     * Returns the string
     *
     * @param string $elementValue
     * @return string
     */
    private function getReturnValue($elementValue): string
    {
        return '<a href="https://developers.facebook.com/tools/debug/accesstoken?access_token='
            . $elementValue . '" target="_blank" title="Debug" style="color:#2b7dbd">Debug</a>'
            . ' | <a href="https://developers.facebook.com/tools/explorer?access_token=' . $elementValue
            . '" target="_blank" title="Try in Graph API Explorer" style="color:#2b7dbd">Try in Graph API Explorer</a>';
    }
}
