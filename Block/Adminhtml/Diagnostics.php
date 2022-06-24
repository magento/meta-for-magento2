<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Adminhtml;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\GraphAPIAdapter;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
use Exception;
use Psr\Log\LoggerInterface;

class Diagnostics extends \Magento\Backend\Block\Template
{
    /**
     * @var FBEHelper
     */
    protected $fbeHelper;
    protected $storeId;


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
     * @param \Magento\Backend\Block\Template\Context $context
     * @param FBEHelper $fbeHelper
     * @param array $data
     */
    public function __construct(
        SystemConfig                            $systemConfig,
        GraphAPIAdapter                         $graphApiAdapter,
        \Magento\Backend\Block\Template\Context $context,
        FBEHelper                               $fbeHelper,
        LoggerInterface                         $logger,
        array                                   $data = []
    )
    {
        $this->systemConfig = $systemConfig;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->fbeHelper = $fbeHelper;
        $this->storeId = $this->fbeHelper->getStore()->getId();
        $this->logger = $logger;
        parent::__construct($context, $data);
    }

    public function getReport()
    {
        $catalogId = $this->systemConfig->getCatalogId($this->storeId);
        $report = [];
        try {
            $response = $this->graphApiAdapter->getCatalogDiagnostics($catalogId);
            if (isset($response['diagnostics']['data'])) {
                $report = $response['diagnostics']['data'];
            }
        } catch (Exception $e) {
            $this->exceptions[] = $e->getMessage();
            $this->logger->critical($e->getMessage());
        }
        return $report;

    }

}
