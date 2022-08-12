<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Adminhtml;

use Exception;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\GraphAPIAdapter;
use Facebook\BusinessExtension\Model\Config\Source\Product\Identifier as IdentifierConfig;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Psr\Log\LoggerInterface;

class Email extends \Magento\Backend\Block\Template
{
    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    protected $storeId;

    protected $commerceAccountId;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var GraphAPIAdapter
     */
    protected $graphApiAdapter;

    /**
     * @var array
     */
    private $exceptions = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphApiAdapter
     * @param Context $context
     * @param FBEHelper $fbeHelper
     * @param LoggerInterface $logger
     * @param CollectionFactory $productCollectionFactory
     * @param array $data
     */
    public function __construct(
        SystemConfig $systemConfig,
        GraphAPIAdapter $graphApiAdapter,
        Context $context,
        FBEHelper $fbeHelper,
        LoggerInterface $logger,
        CollectionFactory $productCollectionFactory,
        array $data = []
    ) {
        $this->systemConfig = $systemConfig;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->fbeHelper = $fbeHelper;
        $this->storeId = $this->fbeHelper->getStore()->getId();
        $this->commerceAccountId = $this->systemConfig->getCommerceAccountId($this->storeId);
        $this->logger = $logger;
        $this->productCollectionFactory = $productCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * @return array
     */
    public function getEmails()
    {
        $emails = [];
        try {
            $response = $this->graphApiAdapter->getLoyaltyMarketingEmails($this->commerceAccountId);
            $emails = $response['data'] ?? [];
        } catch (Exception $e) {
            $this->exceptions[] = $e->getMessage();
            $this->logger->critical($e->getMessage());
        }
        return $emails;
    }
}
