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
namespace Meta\Catalog\Ui\DataProvider\Product\Form\Modifier;

use Meta\Catalog\Block\Adminhtml\Product\Form\Diagnostics;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface as Request;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\View\Element\BlockFactory;

class SendToFacebook extends AbstractModifier
{
    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @var BlockFactory
     */
    protected $blockFactory;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     * @param ScopeConfigInterface $scopeConfig
     * @param BlockFactory $blockFactory
     * @param Request $request
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        ScopeConfigInterface $scopeConfig,
        BlockFactory $blockFactory,
        Request $request,
        SystemConfig $systemConfig
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
        $this->scopeConfig = $scopeConfig;
        $this->blockFactory = $blockFactory;
        $this->request = $request;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Get store Id
     *
     * @return mixed
     */
    private function getStoreId()
    {
        return $this->request->getParam('store');
    }

    /**
     * Get if extension is active
     *
     * @return bool
     */
    private function isActive(): bool
    {
        return $this->systemConfig->isActiveExtension($this->getStoreId());
    }

    /**
     * Adding URL rewrite checkbox to meta
     *
     * @param array $meta
     * @return array
     */
    protected function addFacebookProductDiagnostics(array $meta)
    {
        $path = $this->arrayManager->findPath('send_to_facebook', $meta);
        if ($path) {
            $containerPath = $this->arrayManager->slicePath($path, 0, -1);
            /** @var Diagnostics $block */
            $block = $this->blockFactory->createBlock(Diagnostics::class);
            $block->setStoreId($this->getStoreId())
                ->setProduct($this->locator->getProduct());
            $meta = $this->arrayManager->merge(
                $containerPath,
                $meta,
                ['facebook_product_diagnostics' => [
                    'arguments' => [
                        'data' => ['config' => ['componentType' => 'htmlContent']],
                        'block' => $block,
                    ],
                ]]
            );
        }
        return $meta;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        if ($this->isActive() && $this->locator->getProduct()->getId()
            && $this->locator->getProduct()->getTypeId() === ProductType::TYPE_SIMPLE) {
            $meta = $this->addFacebookProductDiagnostics($meta);
        }
        return $meta;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        return $data;
    }
}
