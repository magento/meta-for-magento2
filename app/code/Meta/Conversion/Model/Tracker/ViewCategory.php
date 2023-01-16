<?php
declare(strict_types=1);

namespace Meta\Conversion\Model\Tracker;

use Magento\Framework\Exception\NoSuchEntityException;
use Meta\Conversion\Api\TrackerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Escaper;

class ViewCategory implements TrackerInterface
{

    private const EVENT_TYPE = "ViewCategory";

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Escaper $escaper
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        Escaper $escaper
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->escaper = $escaper;
    }

    /**
     * @inheritDoc
     */
    public function getEventType(): string
    {
        return self::EVENT_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function getPayload(array $params): array
    {
        $categoryId = $params['categoryId'];
        try {
            $category = $this->categoryRepository->get($categoryId);
        } catch (NoSuchEntityException $e) {
            return [];
        }
        return [
            'content_category' => $this->escaper->escapeQuote($category->getName())
        ];
    }
}
