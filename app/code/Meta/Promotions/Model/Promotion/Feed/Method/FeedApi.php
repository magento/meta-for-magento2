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

namespace Meta\Promotions\Model\Promotion\Feed\Method;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Promotions\Model\Promotion\Feed\Builder;
use Meta\Promotions\Model\Promotion\Feed\PromotionRetriever\PromotionRetriever;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\File\WriteInterface;
use Psr\Log\LoggerInterface;

class FeedApi
{
    private const FEED_FILE_NAME = 'facebook_promotions%s.tsv';
    private const VAR_DIR = 'var';

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var GraphAPIAdapter
     */
    protected $graphApiAdapter;

    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var PromotionRetriever
     */
    protected $promotionRetriever;

    /**
     * @param SystemConfig $systemConfig
     * @param GraphAPIAdapter $graphApiAdapter
     * @param Filesystem $filesystem
     * @param Builder $builder
     * @param LoggerInterface $logger
     * @param PromotionRetriever $promotionRetriever
     */
    public function __construct(
        SystemConfig       $systemConfig,
        GraphAPIAdapter    $graphApiAdapter,
        Filesystem         $filesystem,
        Builder            $builder,
        LoggerInterface    $logger,
        PromotionRetriever $promotionRetriever
    ) {
        $this->systemConfig = $systemConfig;
        $this->graphApiAdapter = $graphApiAdapter;
        $this->fileSystem = $filesystem;
        $this->builder = $builder;
        $this->logger = $logger;
        $this->promotionRetriever = $promotionRetriever;
    }

    /**
     * Write file
     *
     * @param WriteInterface $fileStream
     * @throws FileSystemException
     * @throws Exception
     */
    protected function writeFile(WriteInterface $fileStream)
    {
        $fileStream->writeCsv($this->builder->getHeaderFields(), "\t");

        $total = 0;
        $websiteId = $this->systemConfig->getStoreManager()->getStore($this->storeId)->getWebsiteId();
        $offers = $this->promotionRetriever->retrieve($websiteId);
        foreach ($offers as $offer) {
            try {
                $offer->loadCouponCode();
                $entry = array_values($this->builder->buildPromoEntry($offer));
                $fileStream->writeCsv($entry, "\t");
                $total++;
            } catch (Exception $e) {
                $this->logger->debug(sprintf('Skipped promo: %s', $offer->getName()));
                $this->logger->debug($e);
            }
        }
        $this->logger->debug(sprintf('Generated feed with %d promos.', $total));
    }

    /**
     * Get file name with store code suffix for non-default store (no suffix for default one)
     *
     * @return string
     * @throws NoSuchEntityException
     */
    protected function getFeedFileName()
    {
        $defaultStoreId = $this->systemConfig->getStoreManager()->getDefaultStoreView()->getId();
        $storeCode = $this->systemConfig->getStoreManager()->getStore($this->storeId)->getCode();
        return sprintf(
            self::FEED_FILE_NAME,
            ($this->storeId && $this->storeId !== $defaultStoreId) ? ('_' . $storeCode) : ''
        );
    }

    /**
     * Generate promo feed
     *
     * @return string
     * @throws FileSystemException
     * @throws NoSuchEntityException
     */
    protected function generatePromoFeed()
    {
        $file = 'export/' . $this->getFeedFileName();
        $directory = $this->fileSystem->getDirectoryWrite(self::VAR_DIR);
        $directory->create('export');

        $stream = $directory->openFile($file, 'w+');
        $stream->lock();
        $this->writeFile($stream);
        $stream->unlock();

        return $directory->getAbsolutePath($file);
    }

    /**
     * Execute
     *
     * @param int|null $storeId
     * @return bool|mixed
     * @throws Exception|GuzzleException
     */
    public function execute($storeId = null)
    {
        $this->storeId = $storeId;
        $this->builder->setStoreId($this->storeId);
        $this->graphApiAdapter->setDebugMode($this->systemConfig->isDebugMode($storeId))
            ->setAccessToken($this->systemConfig->getAccessToken($storeId));

        try {
            $file = $this->generatePromoFeed();
            $commercePartnerIntegrationId = $this->systemConfig->getCommercePartnerIntegrationId($storeId);
            return $this->graphApiAdapter->uploadFile(
                $commercePartnerIntegrationId,
                $file,
                'PROMOTIONS',
                'create'
            );
        } catch (Exception $e) {
            $this->logger->critical($e);
            throw $e;
        }
    }
}
