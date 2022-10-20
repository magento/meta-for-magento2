<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Adminhtml;

use Facebook\BusinessExtension\Helper\FBEHelper;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Escaper;
use Facebook\BusinessExtension\Helper\LogOrganization;


class Info extends \Magento\Backend\Block\Template
{
    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadataInterface;

    /**
     * @var Escaper
     */
    private $escaper;



    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param FBEHelper $fbeHelper
     * @param ModuleListInterface $moduleList
     * @param ProductMetadataInterface $productMetadataInterface
     * @param Escaper $escaper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        FBEHelper $fbeHelper,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadataInterface,
        Escaper $escaper,
        array $data = []
    ) {
        $this->fbeHelper = $fbeHelper;
        $this->moduleList = $moduleList;
        $this->productMetadataInterface = $productMetadataInterface;
        $this->escaper = $escaper;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getFBEVersion() {
        return $this->moduleList->getOne("Facebook_BusinessExtension")["setup_version"];
    }

    /**
     * @return string
     */
    public function getMagentoVersion() {
        return $this->productMetadataInterface->getVersion();
    }

    /**
     * @return string|null
     */
    public function fetchPixelId()
    {
        return $this->fbeHelper->getConfigValue('fbpixel/id');
    }

    /**
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getExternalBusinessId()
    {
        return $this->fbeHelper->getFBEExternalBusinessId();
    }

    public function allLogs() {
        $sortedString = implode("\n", LogOrganization::organizeLogs());
        return nl2br($sortedString);
    }

    public function publicIssueLink() {
        $magento_version = $this->getMagentoVersion();
        $plugin_version = $this->getFBEVersion();
        return "https://github.com/facebookincubator/facebook-for-magento2/issues/new?&template=bug-report.yml&magento_version="
        . $magento_version . "&plugin_version=" . $plugin_version;
    }

    public function privateIssueLink() {
        $magento_version = $this->getMagentoVersion();
        $plugin_version = $this->getFBEVersion();
        $extern_bus_id = $this->getExternalBusinessId();
        $pixel_id = $this->fetchPixelId();

        return "https://www.facebook.com/help/contact/224834796277182?Field1264912827263491="
        . $magento_version . "&Field1465992913778514=" . $plugin_version
        . "&Field1500601380300162=" . $extern_bus_id . "&Field2972445263018062=" . $pixel_id;
    }
}
