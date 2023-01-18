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

use Meta\Conversion\Api\TrackerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;

class ViewCategory implements TrackerInterface
{

    const EVENT_TYPE = "ViewCategory";

    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository
    ) { }

    /**
     * @inheritDoc
     */
    public function getEventType(): string
    {
        return self::EVENT_TYPE;
    }

    public function getPayload(array $params): array
    {
        $categoryId = $params['categoryId'];
        $category = $this->categoryRepository->get($categoryId);
        return [
            'content_category' => addslashes($category->getName())
        ];
    }
}
