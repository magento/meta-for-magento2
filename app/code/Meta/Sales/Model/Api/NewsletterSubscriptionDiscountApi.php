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

use Magento\Newsletter\Model\SubscriberFactory;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\Coupon\MassgeneratorFactory;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Sales\Api\NewsletterSubscriptionDiscountApiInterface;
use Meta\Sales\Helper\OrderHelper;
use Magento\Framework\Exception\LocalizedException;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;

/**
 * API class for managing newsletter subscription discount coupons.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NewsletterSubscriptionDiscountApi implements NewsletterSubscriptionDiscountApiInterface
{
    /**
     * @var SubscriberFactory Factory for creating newsletter subscriber instances.
     */
    private SubscriberFactory $subscriberFactory;

    /**
     * @var RuleFactory Factory for creating sales rule instances.
     */
    private RuleFactory $ruleFactory;

    /**
     * @var OrderHelper Helper for order-related functionalities.
     */
    private OrderHelper $orderHelper;

    /**
     * @var FBEHelper Helper for Facebook Business Extension related functionalities.
     */
    private FBEHelper $fbeHelper;

    /**
     * @var MassgeneratorFactory Factory for creating mass coupon code generator instances.
     */
    private MassgeneratorFactory $massGeneratorFactory;

    /**
     * @var NewsletterSubscriptionDiscountStatus Factory for creating mass coupon code generator instances.
     */
    private NewsletterSubscriptionDiscountStatus $newsletterSubscriptionDiscountStatus;

    /**
     * @var Authenticator Authenticator for API requests.
     */
    private Authenticator $authenticator;

    /**
     * Constructor for NewsletterSubscriptionDiscountApi.
     *
     * @param SubscriberFactory $subscriberFactory Factory for creating newsletter subscriber instances.
     * @param RuleFactory $ruleFactory Factory for creating sales rule instances.
     * @param OrderHelper $orderHelper Helper for order-related functionalities.
     * @param FBEHelper $fbeHelper Helper for Facebook Business Extension related functionalities.
     * @param MassgeneratorFactory $massGeneratorFactory Factory for creating mass coupon code generator instances.
     * @param NewsletterSubscriptionDiscountStatus $newsletterSubscriptionDiscountStatus
     * @param Authenticator $authenticator Authenticator for API requests.
     */
    public function __construct(
        SubscriberFactory                    $subscriberFactory,
        RuleFactory                          $ruleFactory,
        OrderHelper                          $orderHelper,
        FBEHelper                            $fbeHelper,
        MassgeneratorFactory                 $massGeneratorFactory,
        NewsletterSubscriptionDiscountStatus $newsletterSubscriptionDiscountStatus,
        Authenticator                        $authenticator
    ) {
        $this->subscriberFactory = $subscriberFactory;
        $this->ruleFactory = $ruleFactory;
        $this->orderHelper = $orderHelper;
        $this->fbeHelper = $fbeHelper;
        $this->massGeneratorFactory = $massGeneratorFactory;
        $this->newsletterSubscriptionDiscountStatus = $newsletterSubscriptionDiscountStatus;
        $this->authenticator = $authenticator;
    }

    /**
     * Subscribes a user for a coupon based on newsletter subscription.
     *
     * @param string $externalBusinessId The external business ID.
     * @param string $email The email address of the subscriber.
     * @param int $ruleId The ID of the sales rule.
     * @return string The generated coupon.
     * @throws LocalizedException If an error occurs during the process.
     */
    public function subscribeForCoupon(string $externalBusinessId, string $email, int $ruleId): string
    {
        $this->authenticator->authenticateRequest();

        $storeId = null;
        try {
            $storeId = (int)$this->orderHelper->getStoreIdByExternalBusinessId($externalBusinessId);
            if ($this->newsletterSubscriptionDiscountStatus->checkSubscriptionStatus(
                $externalBusinessId,
                $email
            )
            ) {
                throw new LocalizedException(__('The buyer is already subscribed to the newsletter.'));
            }
            $rule = $this->ruleFactory->create()->load($ruleId);
            if (!$rule->getId()) {
                throw new LocalizedException(__('The specified discount rule does not exist.'));
            }

            $coupon = $this->generateCoupon((int)$rule->getId());

            // Subscribe the user to the newsletter
            $subscriber = $this->subscriberFactory->create();
            $subscriber->setStoreId($storeId);
            $subscriber->setSubscriberEmail($email);
            $subscriber->setSubscriberStatus(\Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED);
            $subscriber->save();

            return $coupon;
        } catch (\Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'create_email_subscription_discount',
                    'event_type' => 'discount_creation_exception',
                    'extra_data' => [
                        'external_business_id' => $externalBusinessId,
                    ]
                ]
            );
            throw new LocalizedException(
                __('An error occurred while generating the coupon code: %1', $e->getMessage())
            );
        }
    }

    /**
     * Generates a coupon code based on a sales rule ID.
     *
     * @param int $ruleId The sales rule ID.
     * @return string The generated coupon code.
     */
    private function generateCoupon(int $ruleId): string
    {
        $rule = $this->ruleFactory->create()->load($ruleId);
        if (!$rule->getId()) {
            throw new LocalizedException(__('The specified discount rule does not exist.'));
        }

        $generator = $this->massGeneratorFactory->create();
        $generator->setFormat(\Magento\SalesRule\Helper\Coupon::COUPON_FORMAT_ALPHANUMERIC);
        $generator->setRuleId($ruleId);
        $generator->setUsesPerCoupon(1);
        $generator->setDash(3);
        $generator->setLength(9);
        $generator->setPrefix('META_SUBSCRIBER_');
        $generator->setSuffix('');
        $generator->setQty(1);

        $generator->generatePool();
        $coupons = $generator->getGeneratedCodes();

        if (empty($coupons)) {
            throw new LocalizedException(__('Failed to generate coupon code.'));
        }

        return $coupons[0];
    }
}
