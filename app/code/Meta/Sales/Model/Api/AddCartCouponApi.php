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
use Magento\Quote\Model\GuestCart\GuestCouponManagement;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Model\CouponFactory;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;
use Meta\Sales\Api\AddCartCouponApiInterface;
use Meta\Sales\Api\AddCartCouponApiResponseInterface;
use Meta\Sales\Helper\OrderHelper;
use Meta\Sales\Model\Api\AddCartCouponApiResponse;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddCartCouponApi implements AddCartCouponApiInterface
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
     * @var GuestCouponManagement
     */
    private GuestCouponManagement $guestCouponManagement;

    /**
     * @var CouponFactory
     */
    private CouponFactory $couponFactory;

    /**
     * @var RuleRepositoryInterface
     */
    private RuleRepositoryInterface $ruleRepository;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @param Authenticator             $authenticator
     * @param OrderHelper               $orderHelper
     * @param GuestCouponManagement     $guestCouponManagement
     * @param CouponFactory             $couponFactory
     * @param RuleRepositoryInterface   $ruleRepository
     * @param FBEHelper                 $fbeHelper
     */
    public function __construct(
        Authenticator               $authenticator,
        OrderHelper                 $orderHelper,
        GuestCouponManagement       $guestCouponManagement,
        CouponFactory               $couponFactory,
        RuleRepositoryInterface     $ruleRepository,
        FBEHelper                   $fbeHelper
    ) {
        $this->authenticator = $authenticator;
        $this->orderHelper = $orderHelper;
        $this->guestCouponManagement = $guestCouponManagement;
        $this->couponFactory = $couponFactory;
        $this->ruleRepository = $ruleRepository;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Add coupon to Magento cart
     *
     * @param string $externalBusinessId
     * @param string $cartId
     * @param string $couponCode
     * @return \Meta\Sales\Api\AddCartCouponApiResponseInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addCartCoupon(
        string $externalBusinessId,
        string $cartId,
        string $couponCode
    ): AddCartCouponApiResponseInterface {
        $this->authenticator->authenticateRequest();
        $storeId = $this->orderHelper->getStoreIdByExternalBusinessId($externalBusinessId);
        try {
            $response = new AddCartCouponApiResponse();
            $status = $this->guestCouponManagement->set($cartId, $couponCode);
            $response->setStatus($status);
            $rule = $this->getCouponRule($storeId, $cartId, $couponCode);
            $response->setRule($rule);
            return $response;
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
                    'event' => 'add_cart_coupon_api',
                    'event_type' => 'no_such_entity_exception',
                    'extra_data' => [
                        'cart_id' => $cartId,
                        'coupon_code' => $couponCode
                    ]
                ]
            );
            throw $le;
        } catch (\Throwable $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'add_cart_coupon_api',
                    'event_type' => 'error_adding_cart_coupon',
                    'extra_data' => [
                        'cart_id' => $cartId,
                        'coupon_code' => $couponCode
                    ]
                ]
            );
            throw $e;
        }
    }

    /**
     * Get Rule for particular couponCode
     *
     * @param string $storeId
     * @param string $cartId
     * @param string $couponCode
     * @return \Magento\SalesRule\Api\Data\RuleInterface|null
     */
    public function getCouponRule(string $storeId, string $cartId, string $couponCode): ?RuleInterface
    {
        try {
            $coupon = $this->couponFactory->create();
            $coupon->load($couponCode, 'code');
            $rule = $this->ruleRepository->getById($coupon->getRuleId());
            return $rule;
        } catch (\Throwable $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'add_cart_coupon_api',
                    'event_type' => 'error_getting_coupon_rule_from_code',
                    'extra_data' => [
                        'cart_id' => $cartId,
                        'coupon_code' => $couponCode
                    ]
                ]
            );
            return null;
        }
    }
}
