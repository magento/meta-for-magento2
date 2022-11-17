<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Pixel;

use Facebook\BusinessExtension\Helper\AAMFieldsExtractorHelper;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\MagentoDataHelper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;

class Head extends Common
{
    /**
     * @var AAMFieldsExtractorHelper
     */
    protected $aamFieldsExtractorHelper;

    /**
     * Head constructor
     *
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param Registry $registry
     * @param FBEHelper $fbeHelper
     * @param MagentoDataHelper $magentoDataHelper
     * @param AAMFieldsExtractorHelper $aamFieldsExtractorHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        Registry $registry,
        FBEHelper $fbeHelper,
        MagentoDataHelper $magentoDataHelper,
        AAMFieldsExtractorHelper $aamFieldsExtractorHelper,
        array $data = []
    ) {
        parent::__construct($context, $objectManager, $registry, $fbeHelper, $magentoDataHelper, $data);
        $this->aamFieldsExtractorHelper = $aamFieldsExtractorHelper;
    }

    /**
     * Returns the user data that will be added in the pixel init code
     * @return string
     */
    public function getPixelInitCode()
    {
        $userDataArray = $this->aamFieldsExtractorHelper->getNormalizedUserData();

        if ($userDataArray) {
            return json_encode(array_filter($userDataArray), JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);
        }
        return '{}';
    }

    /**
     * Create JS code with the data processing options if required
     * To learn about this options in Meta Pixel, read:
     * https://developers.facebook.com/docs/marketing-apis/data-processing-options
     * @return string
     */
    public function getDataProcessingOptionsJSCode()
    {
        return '';
    }

    /**
     * Create the data processing options passed in the Pixel image tag
     * Read about this options in:
     * https://developers.facebook.com/docs/marketing-apis/data-processing-options
     * @return string
     */
    public function getDataProcessingOptionsImgTag()
    {
        return '';
    }
}
