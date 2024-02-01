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

 namespace Meta\Sales\Model\Api;

 use Meta\Sales\Api\AddCartItemsApiResponseInterface;

class AddCartItemsApiResponse implements AddCartitemsApiResponseInterface
{
    /**
     * @var \Magento\Quote\Api\Data\CartItemInterfaceCartItemInterface[]
     */
    private $itemsAdded = [];

    /**
     * @var array
     */
    private $exceptions = [];

    /**
     * Getter
     *
     * @return \Magento\Quote\Api\Data\CartItemInterfaceCartItemInterface[]
     */
    public function getItemsAdded(): array
    {
        return $this->itemsAdded;
    }

    /**
     * Setter
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface[] $items
     * @return void
     */
    public function setItemsAdded(array $items): void
    {
        $this->itemsAdded = $items;
    }

    /**
     * Getter
     *
     * @return array
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    /**
     * Setter
     *
     * @param array $exceptions
     * @return void
     */
    public function setExceptions(array $exceptions): void
    {
        $this->exceptions = $exceptions;
    }
}
