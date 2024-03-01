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

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\GuestShippingInformationManagement;
use Magento\Checkout\Api\Data\PaymentDetailsInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;
use Meta\Sales\Api\SetCartShippingOptionApiInterface;
use Meta\Sales\Helper\OrderHelper;

class SetCartShippingOptionApi implements SetCartShippingOptionApiInterface
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
     * @var GuestShippingInformationManagement
     */
    private GuestShippingInformationManagement $guestShippingInformationManagement;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @param Authenticator                         $authenticator
     * @param OrderHelper                           $orderHelper
     * @param GuestShippingInformationManagement    $guestShippingInformationManagement
     * @param FBEHelper                             $fbeHelper
     */
    public function __construct(
        Authenticator                           $authenticator,
        OrderHelper                             $orderHelper,
        GuestShippingInformationManagement      $guestShippingInformationManagement,
        FBEHelper                               $fbeHelper
    ) {
        $this->authenticator = $authenticator;
        $this->orderHelper = $orderHelper;
        $this->guestShippingInformationManagement = $guestShippingInformationManagement;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Set Magento cart shipping option
     *
     * @param string $externalBusinessId
     * @param string $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setCartShippingOption(
        string $externalBusinessId,
        string $cartId,
        ShippingInformationInterface $addressInformation
    ): PaymentDetailsInterface {
        $this->authenticator->authenticateRequest();
        $this->authenticator->validateSignature();
        $storeId = $this->orderHelper->getStoreIdByExternalBusinessId($externalBusinessId);
        try {
            return $this->guestShippingInformationManagement->saveAddressInformation($cartId, $addressInformation);
        } catch (NoSuchEntityException $e) {
            $le = new LocalizedException(__(
                "No such entity with cartId = %1",
                $cartId
            ));
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $le,
                [
                    'store_id' => $storeId,
                    'event' => 'set_cart_shipping_option_api',
                    'event_type' => 'no_such_entity_exception',
                    'extra_data' => [
                        'cart_id' => $cartId,
                    ]
                ]
            );
            throw $le;
        } catch (\Throwable $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'set_cart_shipping_option_api',
                    'event_type' => 'error_setting_cart_shipping_options',
                    'extra_data' => [
                        'cart_id' => $cartId
                    ]
                ]
            );
            throw $e;
        }
    }
}
