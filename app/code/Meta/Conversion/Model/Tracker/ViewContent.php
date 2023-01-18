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

declare(strict_types=1);

namespace Meta\Conversion\Model\Tracker;

use Meta\BusinessExtension\Helper\MagentoDataHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Meta\Conversion\Api\TrackerInterface;

class ViewContent implements TrackerInterface
{

    const EVENT_TYPE = "ViewContent";

    public function __construct(
        private readonly MagentoDataHelper $magentoDataHelper,
        private readonly ProductRepositoryInterface $productRepository
    ) { }

    /**
     * @inheritDoc
     */
    public function getEventType(): string
    {
        return self::EVENT_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function getPayload(array $params): array
    {
        $productId = $params['productId'];
        $product = $this->productRepository->getById($productId);
        $contentId = $this->magentoDataHelper->getContentId($product);
        return [
            'currency' => $this->magentoDataHelper->getCurrency(),
            'value' => $this->magentoDataHelper->getValueForProduct($product),
            'content_ids' => [$contentId],
            'content_category' => $this->magentoDataHelper->getCategoriesForProduct($product),
            'content_name' => $product->getName(),
            'contents' => [
                [
                    'id' => $contentId,
                    'item_price' => $this->magentoDataHelper->getValueForProduct($product)
                ]
            ],
            'content_type' => $this->magentoDataHelper->getContentType($product)
        ];
    }
}
