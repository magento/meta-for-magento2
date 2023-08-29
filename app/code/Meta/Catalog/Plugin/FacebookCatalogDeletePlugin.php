<?php

declare(strict_types=1);

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

namespace Meta\Catalog\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Catalog\Helper\Product\Identifier;
use Meta\Catalog\Model\FacebookCatalogUpdateFactory;
use Meta\Catalog\Model\ResourceModel\FacebookCatalogUpdate;

class FacebookCatalogDeletePlugin
{

    /**
     * @var FacebokoCatalogUpdate
     */
    private $catalogUpdateResourceModel;

    /**
     * @var FacebookCatalogUpdateFactory
     */
    private $catalogUpdateFactory;

    /**
     * @var Identifier
     */
    private $identifier;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * FacebookCatalogUpdateOnIndexerPlugin constructor
     *
     * @param FacebookCatalogUpdate $catalogUpdateResourceModel
     * @param FacebookCatalogUpdateFactory $catalogUpdateFactory
     * @param Identifier $identifier
     * @param FBEHelper $fbeHelper
     */
    public function __construct(
        FacebookCatalogUpdate $catalogUpdateResourceModel,
        FacebookCatalogUpdateFactory $catalogUpdateFactory,
        Identifier $identifier,
        FBEHelper $fbeHelper
    ) {
        $this->catalogUpdateResourceModel = $catalogUpdateResourceModel;
        $this->catalogUpdateFactory = $catalogUpdateFactory;
        $this->identifier = $identifier;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * After delete plugin
     *
     * @param Product $subject
     * @param Product $result
     * @param ProductInterface $object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDelete(Product $subject, $result, $object)
    {
        $identifier = $this->identifier->getMagentoProductRetailerId($object);
        if ($identifier === false) {
            $this->fbeHelper->log('Deleted Product does not have meta identifier.');
            return $result;
        }

        $catalogDelete = $this->catalogUpdateFactory->create()
            ->setProductId($object->getId())
            ->setSku($identifier)
            ->setMethod('delete');
        try {
            $this->catalogUpdateResourceModel->deleteUpdateProductEntries($identifier);
            $this->catalogUpdateResourceModel->save($catalogDelete);
        } catch (\Exception $e) {
            $this->fbeHelper->log('Unable to save product deletion to Facebook catalog update table.');
            $this->fbeHelper->logException($e);
        }
        return $result;
    }
}
