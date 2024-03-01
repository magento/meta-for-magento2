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

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\GuestCart\GuestCartItemRepository;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;
use Meta\Sales\Api\DeleteCartItemApiInterface;
use Meta\Sales\Helper\OrderHelper;

class DeleteCartItemApi implements DeleteCartItemApiInterface
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
     * Delete item from Magento cart
     *
     * @param string $externalBusinessId
     * @param string $cartId
     * @param string $itemId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteCartItem(string $externalBusinessId, string $cartId, string $itemId): bool
    {
        $this->authenticator->authenticateRequest();
        $this->authenticator->validateSignature();
        $storeId = $this->orderHelper->getStoreIdByExternalBusinessId($externalBusinessId);
        try {
            return $this->guestCartItemRepository->deleteById($cartId, $itemId);
        } catch (NoSuchEntityException $e) {
            if (strpos($e->getMessage(), 'cartId') !== false) {
                $le = new LocalizedException(__(
                    "No such entity with cartId = %1",
                    $cartId
                ));
            } else {
                $le = $e;
            }
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $le,
                [
                    'store_id' => $storeId,
                    'event' => 'delete_cart_item_api',
                    'event_type' => 'no_such_entity_exception',
                    'extra_data' => [
                        'cart_id' => $cartId,
                        'item_id' => $itemId
                    ]
                ]
            );
            throw $le;
        } catch (\Throwable $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'delete_cart_item_api',
                    'event_type' => 'error_deleting_cart_item',
                    'extra_data' => [
                        'cart_id' => $cartId,
                        'item_id' => $itemId
                    ]
                ]
            );
            throw $e;
        }
    }
}
