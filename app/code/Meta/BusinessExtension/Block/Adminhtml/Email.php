<?php
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

namespace Meta\BusinessExtension\Block\Adminhtml;

use Exception;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Backend\Block\Template;
use Psr\Log\LoggerInterface;

/**
 * @api
 */
class Email extends Template
{
    /**
     * @var FBEHelper
     */
    protected $fbeHelper;

    protected $storeId;

    protected $commerceAccountId;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var GraphAPIAdapter
     */
    protected $graphApiAdapter;

    /**
     * @var array
     */
    private $exceptions = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @param Context $context
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphApiAdapter
     * @param FBEHelper $fbeHelper
     * @param LoggerInterface $logger
     * @param CollectionFactory $productCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        SystemConfig $systemConfig,
        GraphAPIAdapter $graphApiAdapter,
        FBEHelper $fbeHelper,
        LoggerInterface $logger,
        CollectionFactory $productCollectionFactory,
        array $data = []
    ) {
        $this->systemConfig = $systemConfig;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->fbeHelper = $fbeHelper;
        $this->storeId = $this->fbeHelper->getStore()->getId();
        $this->commerceAccountId = $this->systemConfig->getCommerceAccountId($this->storeId);
        $this->logger = $logger;
        $this->productCollectionFactory = $productCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * @return array
     */
    public function getEmails()
    {
        $emails = [];
        try {
            $response = $this->graphApiAdapter->getLoyaltyMarketingEmails($this->commerceAccountId);
            $emails = $response['data'] ?? [];
        } catch (Exception $e) {
            $this->exceptions[] = $e->getMessage();
            $this->logger->critical($e->getMessage());
        }
        return $emails;
    }
}
