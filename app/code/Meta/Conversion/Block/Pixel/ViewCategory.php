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

namespace Meta\Conversion\Block\Pixel;

use Magento\Catalog\Model\Layer as CatalogLayer;
use Magento\Catalog\Model\Layer\Resolver as CatalogLayerResolver;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template\Context;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Conversion\Helper\MagentoDataHelper;

/**
 * @api
 */
class ViewCategory extends Common
{
    /**
     * @var CatalogLayer
     */
    private CatalogLayer $catalogLayer;

    /**
     * Head constructor
     *
     * @param Context $context
     * @param FBEHelper $fbeHelper
     * @param MagentoDataHelper $magentoDataHelper
     * @param SystemConfig $systemConfig
     * @param Escaper $escaper
     * @param CheckoutSession $checkoutSession
     * @param CatalogLayerResolver $catalogLayerResolver
     * @param array $data
     */
    public function __construct(
        Context $context,
        FBEHelper $fbeHelper,
        MagentoDataHelper $magentoDataHelper,
        SystemConfig $systemConfig,
        Escaper $escaper,
        CheckoutSession $checkoutSession,
        CatalogLayerResolver $catalogLayerResolver,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $fbeHelper,
            $magentoDataHelper,
            $systemConfig,
            $escaper,
            $checkoutSession,
            $data
        );
        $this->catalogLayer = $catalogLayerResolver->get();
    }

    /**
     * Get Category name
     *
     * @return string|null
     */
    public function getCategoryName()
    {
        return $this->catalogLayer->getCurrentCategory()->getName();
    }

    /**
     * Get Event name
     *
     * @return string
     */
    public function getEventToObserveName()
    {
        return 'facebook_businessextension_ssapi_view_category';
    }

    /**
     * Get Category Id
     *
     * @return mixed
     */
    public function getCategoryId()
    {
        return $this->catalogLayer->getCurrentCategory()->getId();
    }
}
