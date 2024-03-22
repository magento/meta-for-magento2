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

use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\Framework\Exception\LocalizedException;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\Sales\Api\DiscountRuleApiInterface;
use Meta\Sales\Helper\OrderHelper;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;

class DiscountRuleApi implements DiscountRuleApiInterface
{
    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var OrderHelper
     */
    private OrderHelper $orderHelper;

    /**
     * @var Authenticator
     */
    private Authenticator $authenticator;

    /**
     * DiscountRuleApi constructor.
     *
     * @param RuleRepositoryInterface $ruleRepository
     * @param FBEHelper $fbeHelper
     * @param OrderHelper $orderHelper
     * @param Authenticator $authenticator
     */
    public function __construct(
        RuleRepositoryInterface $ruleRepository,
        FBEHelper $fbeHelper,
        OrderHelper $orderHelper,
        Authenticator $authenticator
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->fbeHelper = $fbeHelper;
        $this->orderHelper = $orderHelper;
        $this->authenticator = $authenticator;
    }

    /**
     * Create a cart rule
     *
     * @param string $externalBusinessId
     * @param RuleInterface $rule
     * @return int
     * @throws LocalizedException
     */
    public function createRule(string $externalBusinessId, RuleInterface $rule): string
    {
        $this->authenticator->authenticateRequest();
        $storeId = $this->orderHelper->getStoreIdByExternalBusinessId($externalBusinessId);
        try {
            $savedRule = $this->ruleRepository->save($rule);
            return (string)$savedRule->getRuleId();
        } catch (\Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'create_discount_rule',
                    'event_type' => 'rule_creation_exception',
                    'extra_data' => [
                        'external_business_id' => $externalBusinessId,
                    ]
                ]
            );
            throw new LocalizedException(__('Failed to create cart rule: %1', $e->getMessage()));
        }
    }
}
