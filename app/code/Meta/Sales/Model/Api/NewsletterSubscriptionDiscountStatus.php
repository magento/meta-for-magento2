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
use Magento\Store\Model\StoreManagerInterface;
use Meta\Sales\Api\NewsletterSubscriptionDiscountStatusApiInterface;
use Meta\Sales\Helper\OrderHelper;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;

class NewsletterSubscriptionDiscountStatus implements NewsletterSubscriptionDiscountStatusApiInterface
{
    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Authenticator
     */
    private $authenticator;

    /**
     * NewsletterSubscriptionDiscountStatus constructor.
     *
     * @param SubscriberFactory $subscriberFactory
     * @param OrderHelper $orderHelper
     * @param StoreManagerInterface $storeManager
     * @param Authenticator $authenticator
     */
    public function __construct(
        SubscriberFactory $subscriberFactory,
        OrderHelper $orderHelper,
        StoreManagerInterface $storeManager,
        Authenticator $authenticator
    ) {
        $this->subscriberFactory = $subscriberFactory;
        $this->orderHelper = $orderHelper;
        $this->storeManager = $storeManager;
        $this->authenticator = $authenticator;
    }

    /**
     * Check the email subscription status of a Magento buyer's email
     *
     * @param string $externalBusinessId
     * @param string $email
     * @return bool
     */
    public function checkSubscriptionStatus(string $externalBusinessId, string $email): bool
    {
        $this->authenticator->authenticateRequest();

        $storeId = $this->orderHelper->getStoreIdByExternalBusinessId($externalBusinessId);
        $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();

        $subscriber = $this->subscriberFactory->create();
        $subscriber->loadBySubscriberEmail($email, $websiteId);

        if ($subscriber->getId()) {
            return (bool)$subscriber->getSubscriberStatus();
        }
        return false;
    }
}
