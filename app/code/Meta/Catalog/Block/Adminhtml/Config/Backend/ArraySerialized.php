<?php
declare(strict_types=1);

namespace Meta\Catalog\Block\Adminhtml\Config\Backend;

use \InvalidArgumentException;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value as ConfigValue;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Meta\BusinessExtension\Logger\Logger;

class ArraySerialized extends ConfigValue
{
    /**
     * @var SerializerInterface
     */
    public $serializer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param SerializerInterface $serializer
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param Logger $logger
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        SerializerInterface $serializer,
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Logger $logger,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @inheritDoc
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        if (is_array($value)) {
            unset($value['__empty']);
        }
        try {
            $encodedValue = $this->serializer->serialize($value);
            $this->setValue($encodedValue);
        } catch (InvalidArgumentException $e) {
            $this->logger->critical($e->getMessage());
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function _afterLoad()
    {
        $value = $this->getValue();
        if ($value) {
            try {
                $decodedValue = $this->serializer->unserialize($value);
                $this->setValue($decodedValue);
            } catch (InvalidArgumentException $e) {
                $this->logger->critical($e->getMessage());
            }
        }
        return $this;
    }
}
