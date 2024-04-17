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

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;
use Meta\Sales\Api\CreateCartApiInterface;
use Meta\Sales\Helper\OrderHelper;

class CreateCartApi implements CreateCartApiInterface
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
     * @var QuoteIdMaskFactory
     */
    private QuoteIdMaskFactory $quoteIdMaskFactory;

    /**
     * @var QuoteFactory
     */
    private QuoteFactory $quoteFactory;

    /**
     * @var AddressFactory
     */
    private AddressFactory $quoteAddressFactory;

    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $quoteRepository;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @param Authenticator           $authenticator
     * @param OrderHelper             $orderHelper
     * @param QuoteIdMaskFactory      $quoteIdMaskFactory
     * @param QuoteFactory            $quoteFactory
     * @param AddressFactory          $quoteAddressFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param FBEHelper               $fbeHelper
     */
    public function __construct(
        Authenticator               $authenticator,
        OrderHelper                 $orderHelper,
        QuoteIdMaskFactory          $quoteIdMaskFactory,
        QuoteFactory                $quoteFactory,
        AddressFactory              $quoteAddressFactory,
        CartRepositoryInterface     $quoteRepository,
        FBEHelper                   $fbeHelper
    ) {
        $this->authenticator = $authenticator;
        $this->orderHelper = $orderHelper;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->quoteFactory = $quoteFactory;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->quoteRepository = $quoteRepository;
        $this->fbeHelper = $fbeHelper;
    }

    /**
     * Create Magento cart
     *
     * @param  string $externalBusinessId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createCart(string $externalBusinessId): string
    {
        $this->authenticator->authenticateRequest();
        $storeId = $this->orderHelper->getStoreIdByExternalBusinessId($externalBusinessId);

        /**
 * @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask 
*/
        $quoteIdMask = $this->quoteIdMaskFactory->create();

        /**
 * @var Quote $quote 
*/
        $quote = $this->quoteFactory->create();
        $quote->setStoreId($storeId);
        $quote->setBillingAddress($this->quoteAddressFactory->create());
        $quote->setShippingAddress($this->quoteAddressFactory->create());
        $quote->setCustomerIsGuest(1);

        try {
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'store_id' => $storeId,
                    'event' => 'create_cart_api',
                    'event_type' => 'quote_save_exception'
                ]
            );
            throw new CouldNotSaveException(__("The quote can't be created."));
        }

        $quoteIdMask->setQuoteId($quote->getId())->save();
        return $quoteIdMask->getMaskedId();
    }
}
