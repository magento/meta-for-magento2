<?php
declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Meta\BusinessExtension\Model\ResourceModel\FacebookInstalledFeature;

class FacebookInstalledFeatureTest extends TestCase
{
    /**
     * Class setUp function
     * 
     * @return void
     */
    public function setUp(): void
    {
        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->getMockForAbstractClass();

        $this->selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceConnectionMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->resourceConnectionMock->expects($this->once())
            ->method('getTableName')
            ->with('facebook_installed_features')
            ->willReturn('facebook_installed_features');

        
        $objectManager = new ObjectManager($this);
        $this->facebookInstalledFeatureMockObj = $objectManager->getObject(
            FacebookInstalledFeature::class,
            [
                '_resources' => $this->resourceConnectionMock,
            ]
        );
    }

    /**
     * Test doesFeatureTypeExist function 
     * 
     * @return void
     */
    public function testDoesFeatureTypeExist(): void
    {
        $featureType = 'some_feature';
        $storeId = 1;
        $expectedResult = ['row_id' => 1, 'feature_type' => $featureType, 'store_id' => $storeId];

        $this->connectionMock->expects($this->once())
            ->method('select')
            ->willReturn($this->selectMock);

        $this->selectMock->expects($this->once())
            ->method('from')
            ->with('facebook_installed_features')
            ->willReturnSelf();

        $this->selectMock->expects($this->exactly(2))
            ->method('where')
            ->withConsecutive(
                ['feature_type = ?', $featureType],
                ['store_id = ?', $storeId]
            )
            ->willReturnSelf();

        $this->connectionMock->expects($this->once())
            ->method('fetchRow')
            ->with($this->selectMock)
            ->willReturn($expectedResult);

        $this->assertTrue($this->facebookInstalledFeatureMockObj->doesFeatureTypeExist($featureType, $storeId));
    }

    /**
     * Test doesFeatureTypeExist method when feature does not exist
     *
     * @return void
     */
    public function testDoesFeatureTypeExistWhenFeatureDoesNotExist(): void
    {
        $featureType = 'another_feature';
        $storeId = 2;

        $this->connectionMock->expects($this->once())
            ->method('select')
            ->willReturn($this->selectMock);

        $this->selectMock->expects($this->once())
            ->method('from')
            ->with('facebook_installed_features')
            ->willReturnSelf();

        $this->selectMock->expects($this->exactly(2))
            ->method('where')
            ->withConsecutive(
                ['feature_type = ?', $featureType],
                ['store_id = ?', $storeId]
            )
            ->willReturnSelf();

        $this->connectionMock->expects($this->once())
            ->method('fetchRow')
            ->with($this->selectMock)
            ->willReturn(false);

        $this->assertFalse($this->facebookInstalledFeatureMockObj->doesFeatureTypeExist($featureType, $storeId));
    }

    /**
     * Test deleteAll function
     * 
     * @return void
     */
    public function testDeleteAll(): void
    {
        $storeId = 2;

        $this->connectionMock->expects($this->once())
            ->method('delete')
            ->willReturn(1);

        $this->assertSame(1, $this->facebookInstalledFeatureMockObj->deleteAll($storeId));
    }

    /**
     * Test saveResponseData function
     * 
     * @return void
     */
    public function testSaveResponseData(): void
    {
        $storeId = 123;
        $features = [
            [
                'connected_assets' => ['asset1', 'asset2'],
                'additional_info' => ['key1' => 'value1'],
            ]
        ];
        $expectedFinalFeatures = [
            [
                'store_id' => $storeId,
                'connected_assets' => '"asset1"',
                'additional_info' => '{"key1":"value1"}',
            ]
        ];

        $facebookInstalledFeaturesTable = 'facebook_installed_features';
            
        $this->connectionMock->expects($this->once())
            ->method('insertOnDuplicate')
            ->with(
                $this->equalTo($facebookInstalledFeaturesTable),
                $this->equalTo($expectedFinalFeatures)
            )
            ->willReturn('');

        $this->facebookInstalledFeatureMockObj->saveResponseData($features, $storeId);
    }

    /**
     * Test saveResponseData function
     * 
     * @return void
     */
    public function testSaveResponseDataWithEmptyAdditionalInfo(): void
    {
        $storeId = 123;
        $features = [
            [
                'connected_assets' => ['asset1', 'asset2'],
                'additional_info' => [],
            ]
        ];
        $expectedFinalFeatures = [
            [
                'store_id' => $storeId,
                'connected_assets' => '"asset1"',
                'additional_info' => '[]',
            ]
        ];

        $facebookInstalledFeaturesTable = 'facebook_installed_features';
            
        $this->connectionMock->expects($this->once())
            ->method('insertOnDuplicate')
            ->with(
                $this->equalTo($facebookInstalledFeaturesTable),
                $this->equalTo($expectedFinalFeatures)
            )
            ->willReturn('');

        $this->facebookInstalledFeatureMockObj->saveResponseData($features, $storeId);
    }
}