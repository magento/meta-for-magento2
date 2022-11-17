<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Promotion\Feed\PromotionRetriever;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollection;

class PromotionRetriever
{
    const LIMIT = 2000;

    protected $storeId;

    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    protected $ruleCollection;

    /**
     * @param FBEHelper $fbeHelper
     */
    public function __construct(FBEHelper $fbeHelper, RuleCollection $ruleCollection
    )
    {
        $this->fbeHelper = $fbeHelper;
        $this->ruleCollection = $ruleCollection;

    }

    /**
     * @param $storeId
     * @return ProductRetrieverInterface|void
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * @param int $limit
     * @return array
     */
    public function retrieve($limit = self::LIMIT): array
    {
        $catalogActiveRule = $this->ruleCollection->create()->addFieldToFilter('is_active', 1);
        return $catalogActiveRule->getItems();

    }

    /**
     * @inheritDoc
     */
    public function getLimit()
    {
        return self::LIMIT;
    }
}
