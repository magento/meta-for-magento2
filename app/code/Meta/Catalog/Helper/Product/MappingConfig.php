<?php
declare(strict_types=1);

namespace Meta\Catalog\Helper\Product;

use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;

class MappingConfig extends AbstractHelper
{
    private const XML_PATH_META_CATALOG_MAPPING =
        'facebook_business_extension/attribute_mapping/custom_attribute_mapping';

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var Json
     */
    private Json $serialize;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * Helper Constructor
     *
     * @param Context $context
     * @param SystemConfig $systemConfig
     * @param Json $serialize
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        Context $context,
        SystemConfig $systemConfig,
        Json $serialize,
        FBEHelper $fbeHelper
    ) {
        parent::__construct($context);
        $this->systemConfig = $systemConfig;
        $this->serialize = $serialize;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Get Meta attribute mapping
     *
     * @param int $storeId
     * @return array
     */
    public function getAttributeMapping(int $storeId): array
    {
        $metaAttrMappings = [];
        try {
            $attributeMaps = $this->scopeConfig->getValue(
                self::XML_PATH_META_CATALOG_MAPPING,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if ($attributeMaps == '' || $attributeMaps == null) {
                return $metaAttrMappings;
            }
            $unserializeAttributeMaps = $this->serialize->unserialize($attributeMaps);

            foreach ($unserializeAttributeMaps as $row) {
                $metaAttrMappings[$row['meta_attributes']] = $row['product_attributes'];
            }
            return $metaAttrMappings;
        } catch (\Throwable $e) {
            $context = [
                'store_id' => $storeId,
                'event' => 'product_feed_creation',
                'event_type' => 'attribute_mapping_fetch',
                'catalog_id' => $this->systemConfig->getCatalogId($storeId),
            ];
            $this->fbeHelper->logExceptionImmediatelyToMeta($e, $context);
        }

        return [];
    }
}
