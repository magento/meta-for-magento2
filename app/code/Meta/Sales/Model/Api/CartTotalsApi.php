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
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Model\GuestCart\GuestCartTotalRepository;
use Magento\Quote\Model\Quote;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;
use Meta\Sales\Api\CartTotalsApiInterface;
use Meta\Sales\Helper\OrderHelper;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Model\QuoteIdMaskFactory;

class CartTotalsApi implements CartTotalsApiInterface
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
     * @var GuestCartTotalRepository
     */
    private GuestCartTotalRepository $guestCartTotalRepository;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var QuoteRepository
     */
    private QuoteRepository $quoteRepository;

    /**
     * @var RuleRepositoryInterface
     */
    private RuleRepositoryInterface $ruleRepository;

    /**
     * @var QuoteIdMaskFactory
     */
    private QuoteIdMaskFactory $quoteIdMaskFactory;

    /**
     * @param Authenticator            $authenticator
     * @param OrderHelper              $orderHelper
     * @param GuestCartTotalRepository $guestCartTotalRepository
     * @param FBEHelper                $fbeHelper
     * @param QuoteRepository          $quoteRepository
     * @param RuleRepositoryInterface  $ruleRepository
     * @param QuoteIdMaskFactory       $quoteIdMaskFactory
     */
    public function __construct(
        Authenticator               $authenticator,
        OrderHelper                 $orderHelper,
        GuestCartTotalRepository    $guestCartTotalRepository,
        FBEHelper                   $fbeHelper,
        QuoteRepository             $quoteRepository,
        RuleRepositoryInterface     $ruleRepository,
        QuoteIdMaskFactory          $quoteIdMaskFactory
    ) {
        $this->authenticator = $authenticator;
        $this->orderHelper = $orderHelper;
        $this->guestCartTotalRepository = $guestCartTotalRepository;
        $this->fbeHelper = $fbeHelper;
        $this->quoteRepository = $quoteRepository;
        $this->ruleRepository = $ruleRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * Get Magento cart totals
     *
     * @param  string $externalBusinessId
     * @param  string $cartId
     * @return \Magento\Quote\Api\Data\TotalsInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCartTotals(string $externalBusinessId, string $cartId): TotalsInterface
    {
        $this->authenticator->authenticateRequest();
        $storeId = $this->orderHelper->getStoreIdByExternalBusinessId($externalBusinessId);
        try {
            $quoteId = (int)$this->quoteIdMaskFactory->create()->load($cartId, 'masked_id')->getQuoteId();
            /**
             * @var Quote $quote
             */
            $quote = $this->quoteRepository->get($quoteId);
            $totals = $this->guestCartTotalRepository->get($cartId);
            $rules = explode(',', $quote->getAppliedRuleIds() ?? "");
            foreach ($rules as $ruleId) {
                if ($ruleId) {
                    $rule = $this->ruleRepository->getById($ruleId);
                    if ($rule->getSimpleAction() === Rule::BUY_X_GET_Y_ACTION) {
                        $attrs = $totals->getExtensionAttributes();
                        $attrs->setBxgyDiscountApplied(true);
                        $totals->setExtensionAttributes($attrs);
                        break 1; // Exit
                    }
                }
            }
            return $totals;
        } catch (NoSuchEntityException $e) {
            $le = new LocalizedException(
                __(
                    "No such entity with cartId = %1",
                    $cartId
                )
            );
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $le,
                [
                    'store_id' => $storeId,
                    'event' => 'cart_totals_api',
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
                    'event' => 'cart_totals_api',
                    'event_type' => 'error_getting_cart_totals',
                    'extra_data' => [
                        'cart_id' => $cartId
                    ]
                ]
            );
            throw $e;
        }
    }
}
