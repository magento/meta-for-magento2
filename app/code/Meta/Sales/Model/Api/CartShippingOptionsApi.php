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
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Model\GuestCart\GuestShippingMethodManagement;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;
use Meta\Sales\Api\CartShippingOptionsApiInterface;
use Meta\Sales\Helper\OrderHelper;

class CartShippingOptionsApi implements CartShippingOptionsApiInterface
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
     * @var GuestShippingMethodManagement
     */
    private GuestShippingMethodManagement $guestShippingMethodManagement;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @param Authenticator                     $authenticator
     * @param OrderHelper                       $orderHelper
     * @param GuestShippingMethodManagement     $guestShippingMethodManagement
     * @param FBEHelper                         $fbeHelper
     */
    public function __construct(
        Authenticator                   $authenticator,
        OrderHelper                     $orderHelper,
        GuestShippingMethodManagement   $guestShippingMethodManagement,
        FBEHelper                       $fbeHelper
    ) {
        $this->authenticator = $authenticator;
        $this->orderHelper = $orderHelper;
        $this->guestShippingMethodManagement = $guestShippingMethodManagement;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Fetch Magento cart shipping options
     *
     * @param string $externalBusinessId
     * @param string $cartId
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface[]
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cartShippingOptions(string $externalBusinessId, string $cartId, AddressInterface $address): array
    {
        $this->authenticator->authenticateRequest();
        $storeId = $this->orderHelper->getStoreIdByExternalBusinessId($externalBusinessId);
        try {
            return $this->guestShippingMethodManagement->estimateByExtendedAddress($cartId, $address);
        } catch (NoSuchEntityException $e) {
            $le = new LocalizedException(__(
                "No such entity with cartId = %1",
                $cartId
            ));
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $le,
                [
                    'store_id' => $storeId,
                    'event' => 'cart_shipping_options_api',
                    'event_type' => 'no_such_entity_exception',
                    'extra_data' => [
                        'cart_id' => $cartId
                    ]
                ]
            );
            throw $le;
        } catch (\Throwable $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'cart_shipping_options_api',
                    'event_type' => 'error_fetching_cart_shipping_options',
                    'extra_data' => [
                        'cart_id' => $cartId
                    ]
                ]
            );
            throw $e;
        }
    }
}
