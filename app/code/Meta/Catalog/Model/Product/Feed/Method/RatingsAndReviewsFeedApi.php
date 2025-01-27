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

namespace Meta\Catalog\Model\Product\Feed\Method;

use Exception;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\System\Config as SystemConfig;
use Meta\Catalog\Model\Product\Feed\Builder\Tools as BuilderTools;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Review\Model\ResourceModel\Rating\Option\Vote\CollectionFactory as VoteCollectionFactory;
use Magento\Review\Model\Review;

class RatingsAndReviewsFeedApi
{
    private const FEED_FILE_NAME = 'meta_ratings_and_reviews%s.csv';
    private const VAR_DIR = 'var';

    /**
     * @var int
     */
    protected int $storeId;

    /**
     * @var FBEHelper
     */
    private FBEHelper $fbeHelper;

    /**
     * @var SystemConfig
     */
    protected SystemConfig $systemConfig;

    /**
     * @var BuilderTools
     */
    private $builderTools;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var Filesystem
     */
    protected Filesystem $fileSystem;

    /**
     * @var ReviewCollectionFactory
     */
    private ReviewCollectionFactory $reviewCollectionFactory;

    /**
     * @var VoteCollectionFactory
     */
    private VoteCollectionFactory $voteCollectionFactory;

    /**
     * @param FBEHelper $helper
     * @param SystemConfig $systemConfig
     * @param BuilderTools $builderTools
     * @param ProductRepositoryInterface $productRepository
     * @param Filesystem $filesystem
     * @param ReviewCollectionFactory $reviewCollectionFactory
     * @param VoteCollectionFactory $voteCollectionFactory
     */
    public function __construct(
        FBEHelper                   $helper,
        SystemConfig                $systemConfig,
        BuilderTools                $builderTools,
        ProductRepositoryInterface  $productRepository,
        Filesystem                  $filesystem,
        ReviewCollectionFactory     $reviewCollectionFactory,
        VoteCollectionFactory       $voteCollectionFactory
    ) {
        $this->fbeHelper = $helper;
        $this->systemConfig = $systemConfig;
        $this->builderTools = $builderTools;
        $this->productRepository = $productRepository;
        $this->fileSystem = $filesystem;
        $this->reviewCollectionFactory = $reviewCollectionFactory;
        $this->voteCollectionFactory = $voteCollectionFactory;
    }

    /**
     * Execute
     *
     * @param int $storeId
     * @return null
     * @throws Exception
     */
    public function execute(int $storeId): mixed
    {
        $this->storeId = $storeId;

        try {
            $store = $this->systemConfig->getStoreManager()->getStore($this->storeId);
            $ratingsAndReviewsData = [];

            // Fetch store data
            $ratingsAndReviewsData['aggregator'] = "Magento";
            $storeData = [
                'name' => $store->getName(),
                'id' => $this->systemConfig->getCommerceAccountId($storeId),
                'storeUrls' => [$store->getBaseUrl(FBEHelper::URL_TYPE_WEB)]
            ];
            $ratingsAndReviewsData['store'] = $storeData;

            // Fetch ratings and reviews data
            $reviewCollection = $this->reviewCollectionFactory->create()
                ->addStoreFilter($storeId)
                ->addStatusFilter(Review::STATUS_APPROVED);
            $countryCode = $store->getConfig('general/country/default');
            $reviews = [];
            foreach ($reviewCollection as $review) {
                $reviewData = [
                    'reviewID' => $review->getReviewId(),
                    'rating' => $this->getRatingsVotesForReview($review->getReviewId()),
                    'title' => $review->getTitle(),
                    'content' => $review->getDetail(),
                    'createdAt' => $review->getCreatedAt(),
                    'country' => $countryCode,
                    'reviewer' => [
                        'name' => $review->getNickname()
                    ],
                    'product' => $this->getProductDataForReview($review->getEntityPkValue())
                ];
                if ($reviewData['rating'] !== null) {
                    $reviews[] = $reviewData;
                }
            }

            $ratingsAndReviewsData['reviews'] = $reviews;

            $this->generateRatingsAndReviewsFeedUploadFile($ratingsAndReviewsData);

            // TODO make graph API call with CSV file to sync R&R data

            return null;
        } catch (Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                  'store_id' => $storeId,
                  'event' => 'ratings_and_reviews_sync',
                  'event_type' => 'feed_upload',
                  'catalog_id' => $this->systemConfig->getCatalogId($storeId),
                ]
            );
        }
        return null;
    }

    /**
     * Get ratings votes for review
     *
     * @param int $reviewId
     * @return int|null
     */
    private function getRatingsVotesForReview($reviewId)
    {
        $voteSum = 0;
        $voteCount = 0;
        $voteCollection = $this->voteCollectionFactory->create()
            ->addFieldToFilter('review_id', $reviewId);
        foreach ($voteCollection as $vote) {
            $voteSum += $vote->getValue();
            $voteCount++;
        }
        if ($voteCount > 0) {
            return (int) round($voteSum / $voteCount);
        }
        return null;
    }

    /**
     * Get product data for review
     *
     * @param int $productId
     * @return array
     */
    private function getProductDataForReview($productId)
    {
        $product = $this->productRepository->getById($productId);
        $productData = [
            'name' => $product->getName(),
            'url' => $this->builderTools->replaceLocalUrlWithDummyUrl($product->getProductUrl()),
            'imageUrls' => [$this->builderTools->replaceLocalUrlWithDummyUrl($product->getImage())],
            'productIdentifiers' => [
                'skus' => [$product->getSku()]
            ]
        ];
        return $productData;
    }

    /**
     * Generate ratings and reviews feed upload file
     *
     * @param array $ratingsAndReviewsData
     * @return string
     * @throws FileSystemException
     * @throws NoSuchEntityException
     */
    private function generateRatingsAndReviewsFeedUploadFile($ratingsAndReviewsData): string
    {
        $filePath = 'export/' . $this->getFeedFileName();
        $directory = $this->fileSystem->getDirectoryWrite(self::VAR_DIR);
        $directory->create('export');
        $csvString = $this->generateCsvString($ratingsAndReviewsData);
        $directory->writeFile($filePath, $csvString);
        return $directory->getAbsolutePath($filePath);
    }

    /**
     * Get file name with store code suffix for non-default store (no suffix for default one)
     *
     * @return string
     * @throws NoSuchEntityException
     */
    private function getFeedFileName(): string
    {
        $defaultStoreId = $this->systemConfig->getStoreManager()->getDefaultStoreView()->getId();
        $storeCode = $this->systemConfig->getStoreManager()->getStore($this->storeId)->getCode();
        return sprintf(
            self::FEED_FILE_NAME,
            ($this->storeId && $this->storeId !== $defaultStoreId) ? ('_' . $storeCode) : ''
        );
    }

    /**
     * Generate string to write into feed upload file (CSV)
     *
     * @param array $ratingsAndReviewsData
     * @return string
     */
    private function generateCsvString($ratingsAndReviewsData): string
    {
        $csvString = '';
        $csvColumns = [
            "aggregator",
            "store.name",
            "store.id",
            "store.storeUrls",
            "reviewID",
            "rating",
            "title",
            "content",
            "createdAt",
            "country",
            "reviewer.name",
            "product.name",
            "product.url",
            "product.imageUrls",
            "product.productIdentifiers.skus"
        ];
        $csvRows = [];
        foreach ($ratingsAndReviewsData['reviews'] as $review) {
            $row = [
                $ratingsAndReviewsData['aggregator'],
                $ratingsAndReviewsData['store']['name'],
                $ratingsAndReviewsData['store']['id'],
                json_encode($ratingsAndReviewsData['store']['storeUrls']),
                $review['reviewID'],
                $review['rating'],
                $review['title'],
                $review['content'],
                $review['createdAt'],
                $review['country'],
                $review['reviewer']['name'],
                $review['product']['name'],
                $review['product']['url'],
                json_encode($review['product']['imageUrls']),
                json_encode($review['product']['productIdentifiers']['skus'])
            ];
            $csvRows[] = $row;
        }
        foreach ($csvColumns as $column) {
            $csvString .= '"' . $column . '",';
        }
        $csvString .= "\n";
        foreach ($csvRows as $row) {
            foreach ($row as $value) {
                $csvString .= '"' . $value . '",';
            }
            $csvString .= "\n";
        }
        return $csvString;
    }
}
