<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Config\Backend;

use Facebook\BusinessExtension\Model\System\Config as SystemConfig;

class Catalog extends \Magento\Framework\App\Config\Value
{
    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        SystemConfig $systemConfig,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->systemConfig = $systemConfig;
    }

    public function beforeSave()
    {
        if ($this->isValueChanged()) {
            $this->systemConfig->deleteConfig(SystemConfig::XML_PATH_FACEBOOK_BUSINESS_EXTENSION_FEED_ID);
        }
        return $this;
    }
}
