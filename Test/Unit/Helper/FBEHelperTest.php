<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Test\Unit\Helper;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Logger\Logger;
use Facebook\BusinessExtension\Model\Config;
use Facebook\BusinessExtension\Model\ConfigFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

class FBEHelperTest extends \PHPUnit\Framework\TestCase
{

    protected $fbeHelper;

    protected $context;

    protected $objectManagerInterface;

    protected $configFactory;

    protected $logger;

    protected $directorylist;

    protected $storeManager;

    protected $curl;

    protected $resourceConnection;

    protected $moduleList;

  /**
   * Used to reset or change values after running a test
   *
   * @return void
   */
    public function tearDown()
    {
    }

  /**
   * Used to set the values before running a test
   *
   * @return void
   */
    public function setUp()
    {
        $this->context = $this->createMock(Context::class);
        $this->objectManagerInterface = $this->createMock(ObjectManagerInterface::class);
        $this->configFactory = $this->createMock(ConfigFactory::class);
        $this->logger = $this->createMock(Logger::class);
        $this->directorylist = $this->createMock(DirectoryList::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->curl = $this->createMock(Curl::class);
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->moduleList = $this->createMock(ModuleListInterface::class);

        $this->fbeHelper = new FBEHelper(
            $this->context,
            $this->objectManagerInterface,
            $this->configFactory,
            $this->logger,
            $this->directorylist,
            $this->storeManager,
            $this->curl,
            $this->resourceConnection,
            $this->moduleList
        );
    }

    private function createRowWithValue($configValue)
    {
        $configRow = $this->getMockBuilder(Config::class)
                  ->disableOriginalConstructor()
                  ->setMethods(['getConfigValue', 'load'])
                  ->getMock();
        if ($configValue == null) {
            $configRow->method('load')->willReturn(null);
        } else {
            $configRow->method('load')->willReturn($configRow);
            $configRow->method('getConfigValue')->willReturn($configValue);
        }
        return $configRow;
    }

  /**
   * Test that the returned access token is null when there is no row in the database
   *
   * @return void
   */
    public function testAccessTokenNullWhenNotPresentInDb()
    {
        $configRow = $this->createRowWithValue(null);

        $this->configFactory->method('create')
                        ->willReturn($configRow);

        $this->assertNull($this->fbeHelper->getAccessToken());
    }

  /**
   * Test that the returned access token is not null when there is a row in the database
   *
   * @return void
   */
    public function testAccessTokenNotNullWhenPresentInDb()
    {
        $dummyToken = '1234';
        $configRow = $this->createRowWithValue($dummyToken);

        $this->configFactory->method('create')
                        ->willReturn($configRow);

        $this->assertEquals($dummyToken, $this->fbeHelper->getAccessToken());
    }

  /**
   * Test that the returned aam settings are null when there is no row in the database
   *
   * @return void
   */
    public function testAAMSettingsNullWhenNotPresentInDb()
    {
        $configRow = $this->createRowWithValue(null);

        $this->configFactory->method('create')
                        ->willReturn($configRow);

        $this->assertNull($this->fbeHelper->getAAMSettings());
    }

  /**
   * Test that the returned aam settings are not null when there is a row in the database
   *
   * @return void
   */
    public function testAAMSettingsNotNullWhenPresentInDb()
    {
        $settingsAsArray = [
        "enableAutomaticMatching"=>false,
        "enabledAutomaticMatchingFields"=>['em'],
        "pixelId"=>"1234"
        ];
        $settingsAsString = json_encode($settingsAsArray);

        $configRow = $this->createRowWithValue($settingsAsString);

        $this->configFactory->method('create')
                        ->willReturn($configRow);

        $settings = $this->fbeHelper->getAAMSettings();

        $this->assertEquals($settings->getEnableAutomaticMatching(), $settingsAsArray['enableAutomaticMatching']);
        $this->assertEquals(
            $settings->getEnabledAutomaticMatchingFields(),
            $settingsAsArray['enabledAutomaticMatchingFields']
        );
        $this->assertEquals($settings->getPixelId(), $settingsAsArray['pixelId']);
    }

  /**
   * Test partner agent is correct
   *
   * @return void
   */
    public function testCorrectPartnerAgent()
    {
        $magentoVersion = '2.3.5';
        $pluginVersion = "1.0.0";
        $this->moduleList->method("getOne")->willReturn(
            [
            "setup_version" => $pluginVersion
            ]
        );
        $source = $this->fbeHelper->getSource();
        $productMetadata = $this->getMockBuilder(ProductMetadataInterface::class)
                  ->disableOriginalConstructor()
                  ->setMethods(['getVersion', 'getEdition','getName'])
                  ->getMock();
        $productMetadata->method('getVersion')->willReturn($magentoVersion);
        $productMetadata->method('getVersion')->willReturn($magentoVersion);
        $this->objectManagerInterface->method('get')->willReturn($productMetadata);
        $this->assertEquals(
            sprintf('%s-%s-%s', $source, $magentoVersion, $pluginVersion),
            $this->fbeHelper->getPartnerAgent()
        );
    }
}
