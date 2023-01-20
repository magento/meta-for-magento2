<?php

declare(strict_types=1);

namespace Meta\Conversion\Model\Tracker;

use Magento\Framework\Exception\NoSuchEntityException;
use Meta\Conversion\Api\TrackerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;

class ViewCategory implements TrackerInterface
{

    const EVENT_TYPE = "ViewCategory";

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @inheritDoc
     */
    public function getEventType(): string
    {
        return self::EVENT_TYPE;
    }

    public function getPayload(array $params): array
    {
        $categoryId = $params['categoryId'];
        try {
            $category = $this->categoryRepository->get($categoryId);
        } catch (NoSuchEntityException $e) {
            return [];
        }
        return [
            'content_category' => addslashes($category->getName())
        ];
    }
}
