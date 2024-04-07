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

namespace Meta\Sales\Model\Order;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\SalesSequence\Model\Manager as OrderSequenceManager;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Sales\Api\Data\FacebookOrderInterface;
use Meta\Sales\Api\Data\FacebookOrderInterfaceFactory;
use Meta\Sales\Model\Config\Source\DefaultOrderStatus;
use Meta\Sales\Model\Mapper\OrderMapper;
use Psr\Log\LoggerInterface;

/**
 * Create order from facebook api data
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class CreateOrder
{
    /**
     * @var ManagerInterface
     */
    private ManagerInterface $eventManager;

    /**
     * @var OrderManagementInterface
     */
    private OrderManagementInterface $orderManagement;

    /**
     * @var SystemConfig
     */
    private SystemConfig $systemConfig;

    /**
     * @var FacebookOrderInterfaceFactory
     */
    private FacebookOrderInterfaceFactory $facebookOrderFactory;

    /**
     * @var InvoiceManagementInterface
     */
    private InvoiceManagementInterface $invoiceManagement;

    /**
     * @var TransactionFactory
     */
    private TransactionFactory $transactionFactory;

    /**
     * @var OrderMapper
     */
    private OrderMapper $orderMapper;

    /**
     * @var OrderSequenceManager
     */
    private OrderSequenceManager $orderSequenceManager;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ManagerInterface $eventManager
     * @param OrderManagementInterface $orderManagement
     * @param SystemConfig $systemConfig
     * @param FacebookOrderInterfaceFactory $facebookOrderFactory
     * @param InvoiceManagementInterface $invoiceManagement
     * @param TransactionFactory $transactionFactory
     * @param OrderMapper $orderMapper
     * @param OrderSequenceManager $orderSequenceManager
     * @param LoggerInterface $logger
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        ManagerInterface              $eventManager,
        OrderManagementInterface      $orderManagement,
        SystemConfig                  $systemConfig,
        FacebookOrderInterfaceFactory $facebookOrderFactory,
        InvoiceManagementInterface    $invoiceManagement,
        TransactionFactory            $transactionFactory,
        OrderMapper                   $orderMapper,
        OrderSequenceManager          $orderSequenceManager,
        LoggerInterface               $logger,
        OrderRepository               $orderRepository
    ) {
        $this->eventManager = $eventManager;
        $this->orderManagement = $orderManagement;
        $this->systemConfig = $systemConfig;
        $this->facebookOrderFactory = $facebookOrderFactory;
        $this->invoiceManagement = $invoiceManagement;
        $this->transactionFactory = $transactionFactory;
        $this->orderMapper = $orderMapper;
        $this->orderSequenceManager = $orderSequenceManager;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Generate a new increment order ID and associate it with a current order
     *
     * @param Order $order
     * @return string|null
     * @throws LocalizedException
     */
    public function reserveOrderIncrementId(Order $order): ?string
    {
        $reservedOrderId = $this->orderSequenceManager
            ->getSequence(Order::ENTITY, $order->getStoreId())
            ->getNextValue();
        $order->setIncrementId($reservedOrderId);
        return $reservedOrderId;
    }

    /**
     * Create order without a quote to honor FB totals and tax calculations
     *
     * @param array $data
     * @param int $storeId
     * @return Order
     * @throws GuzzleException
     * @throws LocalizedException
     */
    public function execute(array $data, int $storeId): Order
    {
        $facebookOrderId = $data['id'];

        $this->logger->debug(json_encode($data));

        $order = $this->orderMapper->map($data, $storeId);
        $this->logger->info(json_encode($order));
        $this->reserveOrderIncrementId($order);
        $channel = ucfirst($data['channel']);

        $this->orderManagement->place($order);
        $payment = $order->getPayment();
        $payment->setMethod('facebook');
        $payment->setParentTransactionId($facebookOrderId);

        $extraData = [
            'email_remarketing_option' => $data['buyer_details']['email_remarketing_option'],
        ];

        /** @var FacebookOrderInterface $facebookOrder */
        $facebookOrder = $this->facebookOrderFactory->create();
        $facebookOrder->setFacebookOrderId($facebookOrderId)
            ->setMagentoOrderId($order->getId())
            ->setChannel($channel)
            ->setExtraData($extraData);
        $facebookOrder->save();

        // set order extension attributes
        $emailRemarketingOption = ($extraData['email_remarketing_option'] ?? false) === true;
        $extensionAttributes = $order->getExtensionAttributes();
        $extensionAttributes->setFacebookOrderId($facebookOrder->getFacebookOrderId())
            ->setChannel($facebookOrder->getChannel())
            ->setEmailRemarketingOption($emailRemarketingOption);
        $order->setExtensionAttributes($extensionAttributes);

        $this->eventManager->dispatch('facebook_order_create_after', [
            'order' => $order,
            'facebook_order' => $facebookOrder,
        ]);

        return $order;
    }
}
