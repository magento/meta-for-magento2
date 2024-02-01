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

use Magento\Quote\Model\GuestCart\GuestCartItemRepository;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;
use Meta\Sales\Api\AddCartItemsApiInterface;
use Meta\Sales\Api\AddCartItemsApiResponseInterface;
use Meta\Sales\Helper\OrderHelper;
use Meta\Sales\Model\Api\AddCartItemsApiResponse;

class AddCartItemsApi implements AddCartItemsApiInterface
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
        GuestCartitemRepository     $guestCartItemRepository,
        FBEHelper                   $fbeHelper
    ) {
        $this->authenticator = $authenticator;
        $this->orderHelper = $orderHelper;
        $this->guestCartItemRepository = $guestCartItemRepository;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Add items to Magento cart
     *
     * @param string $externalBusinessId
     * @param \Magento\Quote\Api\Data\CartItemInterface[] $items
     * @return \Meta\Sales\Api\AddCartItemsApiResponseInterface
     * @throws UnauthorizedTokenException
     * @throws LocalizedException
     */
    public function addCartItems(string $externalBusinessId, array $items): AddCartItemsApiResponseInterface
    {
        $this->authenticator->authenticateRequest();
        $storeId = $this->orderHelper->getStoreIdByExternalBusinessId($externalBusinessId);
        $itemsAdded = [];
        $exceptions = [];
        foreach ($items as $item) {
            try {
                $itemsAdded[] = $this->guestCartItemRepository->save($item);
            } catch (\Exception $e) {
                $exceptions[] = [
                    'sku' => $item->getSku(),
                    'exception_message' => $e->getMessage()
                ];
                $this->fbeHelper->logExceptionImmediatelyToMeta(
                    $e,
                    [
                        'store_id' => $storeId,
                        'event' => 'add_cart_items_api',
                        'event_type' => 'error_adding_item',
                        'extra_data' => [
                            'cart_id' => $item->getQuoteId(),
                            'sku' => $item->getSku()
                        ]
                    ]
                );
            }
        }
        $response = new AddCartItemsApiResponse();
        $response->setItemsAdded($itemsAdded);
        $response->setExceptions($exceptions);
        return $response;
    }
}
