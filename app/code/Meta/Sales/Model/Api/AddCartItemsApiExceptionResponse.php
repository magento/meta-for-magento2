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

use Meta\Sales\Api\AddCartItemsApiExceptionResponseInterface;

/**
 * Class AddCartItemsApiExceptionResponse
 *
 * Implements the AddCartItemsApiExceptionResponseInterface to provide detailed information about exceptions
 * that occur when adding items to the cart.
 */
class AddCartItemsApiExceptionResponse implements AddCartItemsApiExceptionResponseInterface
{
    /**
     * @var string The SKU of the item that failed to be added to the cart.
     */
    public string $sku;

    /**
     * @var string The message associated with the exception.
     */
    public string $exceptionMessage;

    /**
     * Constructor for AddCartItemsApiExceptionResponse.
     *
     * @param string $sku The SKU of the item that failed to be added.
     * @param string $exceptionMessage The exception message.
     */
    public function __construct(string $sku, string $exceptionMessage)
    {
        $this->sku = $sku;
        $this->exceptionMessage = $exceptionMessage;
    }

    /**
     * Get the SKU of the item that failed to be added to the cart.
     *
     * @return string The SKU of the failed item.
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * Get the message associated with the exception.
     *
     * @return string The exception message.
     */
    public function getExceptionMessage(): string
    {
        return $this->exceptionMessage;
    }
}
