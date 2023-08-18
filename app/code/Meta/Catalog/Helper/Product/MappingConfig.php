<?php
declare(strict_types=1);

namespace Meta\Catalog\Helper\Product;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Logger\Logger;

class MappingConfig extends AbstractHelper
{
    private const XML_PATH_META_CATALOG_MAPPING =
        'facebook_business_extension/attribute_mapping/custom_attribute_mapping';

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var Json
     */
    private Json $serialize;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Helper Constructor
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Json $serialize
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Json $serialize,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->serialize = $serialize;
        $this->logger = $logger;
    }

    /**
     * Get Meta attribute mapping
     *
     * @return array
     * @throws LocalizedException
     */
    public function getAttributeMapping(): array
    {
        $metaAttrMappings = [];
        try {
            $attributeMaps = $this->scopeConfig->getValue(
                self::XML_PATH_META_CATALOG_MAPPING,
                ScopeInterface::SCOPE_STORE,
                $this->_getRequest()->getParam('store')
            );
            if ($attributeMaps == '' || $attributeMaps == null) {
                return $metaAttrMappings;
            }
            $unserializeAttributeMaps = $this->serialize->unserialize($attributeMaps);

            foreach ($unserializeAttributeMaps as $row) {
                $metaAttrMappings[$row['meta_attributes']] = $row['product_attributes'];
            }
            return $metaAttrMappings;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return [];
    }
}
