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

use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\GuestCart\GuestCartItemRepository;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;
use Meta\Sales\Api\UpdateCartItemApiInterface;
use Meta\Sales\Helper\OrderHelper;

class UpdateCartItemApi implements UpdateCartItemApiInterface
{
    /**
     * @var Authenticator
     */
    private Authenticator $authenticator;

    /**
     * @var OrderHelper
     */
    private OrderHelper $orderHelper;

    /**
     * @var GuestCartItemRepository
     */
    private GuestCartItemRepository $guestCartItemRepository;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @param Authenticator             $authenticator
     * @param OrderHelper               $orderHelper
     * @param GuestCartItemRepository   $guestCartItemRepository
     * @param FBEHelper                 $fbeHelper
     */
    public function __construct(
        Authenticator               $authenticator,
        OrderHelper                 $orderHelper,
        GuestCartItemRepository     $guestCartItemRepository,
        FBEHelper                   $fbeHelper
    ) {
        $this->authenticator = $authenticator;
        $this->orderHelper = $orderHelper;
        $this->guestCartItemRepository = $guestCartItemRepository;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Update Magento cart item
     *
     * @param string $externalBusinessId
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return \Magento\Quote\Api\Data\CartItemInterface
     * @throws \Magento\Framework\Exception\UnauthorizedTokenException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function updateCartItem(string $externalBusinessId, CartItemInterface $cartItem): CartItemInterface
    {
        $this->authenticator->authenticateRequest();
        $this->authenticator->validateSignature();
        $storeId = $this->orderHelper->getStoreIdByExternalBusinessId($externalBusinessId);
        try {
            return $this->guestCartItemRepository->save($cartItem);
        } catch (\Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'update_cart_item_api',
                    'event_type' => 'error_updating_cart_item',
                    'extra_data' => [
                        'cart_id' => $cartItem->getQuoteId(),
                        'sku' => $cartItem->getSku()
                    ]
                ]
            );
            throw $e;
        }
    }
}
