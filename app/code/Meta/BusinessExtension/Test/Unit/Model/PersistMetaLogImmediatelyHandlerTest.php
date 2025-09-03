<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Model;

use PHPunit\Framework\TestCase;
use Meta\BusinessExtension\Model\PersistMetaLogImmediatelyHandler;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;

class PersistMetaLogImmediatelyHandlerTest extends TestCase
{
    /**
     * Class setUp function
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->graphAPIAdapter = $this->createMock(GraphAPIAdapter::class);
        $this->systemConfig = $this->createMock(SystemConfig::class);

        $this->persistMetaLogImmediatelyHandlerMockObj = new PersistMetaLogImmediatelyHandler(
            $this->graphAPIAdapter,
            $this->systemConfig
        );
    }

    /**
     * Test persistMetaLogImmediately function
     *
     * @return void
     */
    public function testPersistMetaLogImmediately(): void
    {
        $content = '{"store_id":99999,"extra_data": {"meta":"object"}}';
        $storeId = 99999;
        $accessToken = 'ACCESS_TOKEN';

        $this->systemConfig
            ->method('getAccessToken')
            ->with(
                $this->equalTo($storeId)
            )
            ->willReturn($accessToken);

        $this->graphAPIAdapter
            ->method('getFBEInstalls')
            ->with(
                $this->equalTo($content),
                $this->equalTo($accessToken)
            )
            ->willReturn('');

        $this->persistMetaLogImmediatelyHandlerMockObj->persistMetaLogImmediately($content);
    }
}
