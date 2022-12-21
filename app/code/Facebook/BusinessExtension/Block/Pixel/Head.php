<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Facebook\BusinessExtension\Block\Pixel;

use Facebook\BusinessExtension\Helper\AAMFieldsExtractorHelper;
use Facebook\BusinessExtension\Helper\FBEHelper;
use Facebook\BusinessExtension\Helper\MagentoDataHelper;
use Facebook\BusinessExtension\Model\System\Config as SystemConfig;
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
     * @param SystemConfig $systemConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        Registry $registry,
        FBEHelper $fbeHelper,
        MagentoDataHelper $magentoDataHelper,
        AAMFieldsExtractorHelper $aamFieldsExtractorHelper,
        SystemConfig $systemConfig,
        array $data = []
    ) {
        parent::__construct($context, $objectManager, $registry, $fbeHelper, $magentoDataHelper, $systemConfig, $data);
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
