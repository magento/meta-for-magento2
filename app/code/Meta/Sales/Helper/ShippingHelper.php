<?php

namespace Meta\Sales\Helper;

use Exception;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order\Shipment\Track;
use Psr\Log\LoggerInterface;

class ShippingHelper extends AbstractHelper
{
    /**
     * @var RegionFactory
     */
    protected $regionFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string[string] Supported carrier names by carrier code.
     */
    private $supportedShippingCarriers = [];

    /**
     * Constructor
     *
     * @param Context $context
     * @param RegionFactory $regionFactory
     * @param LoggerInterface $logger
     * @param array $supportedShippingCarriers
     */
    public function __construct(
        Context $context,
        RegionFactory $regionFactory,
        LoggerInterface $logger,
        array $supportedShippingCarriers = []
    ) {
        parent::__construct($context);
        $this->regionFactory = $regionFactory;
        $this->logger = $logger;
        $this->supportedShippingCarriers = $supportedShippingCarriers;
    }

    /**
     * Array of FB supported shipping carriers
     *
     * Format: CARRIER_CODE => Carrier Title
     * Source: https://developers.facebook.com/docs/commerce-platform/order-management/carrier-codes
     *
     * @return array
     */
    public function getFbSupportedShippingCarriers()
    {
        return $this->supportedShippingCarriers;
    }

    /**
     * Gets the region name from state code
     *
     * @param int $stateId - State code
     * @return string
     */
    public function getRegionName($stateId)
    {
        try {
            $region = $this->regionFactory->create();
            return $region->load($stateId)['code'] ?? $stateId;
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
        }
        return $stateId;
    }

    /**
     * A map for popular US carriers with long titles
     *
     * @return array
     */
    protected function getSupplementaryCarriersMap()
    {
        return [
            'UPS'   => 'United Parcel Service',
            'USPS'  => 'United States Postal Service',
            'FEDEX' => 'Federal Express',
        ];
    }

    /**
     * Find code by title
     *
     * @param string $carrierTitle
     * @param array $carriersMap
     * @return string|false
     */
    protected function findCodeByTitle($carrierTitle, array $carriersMap)
    {
        foreach ($carriersMap as $code => $title) {
            if (stripos($carrierTitle, $title) !== false || stripos($carrierTitle, $code) !== false) {
                return $code;
            }
        }
        return false;
    }

    /**
     * Get canonical carrier Code
     *
     * @param Track $track
     * @return string
     */
    protected function getCanonicalCarrierCode($track)
    {
        $carrierCode = strtoupper($track->getCarrierCode());
        $carrierTitle = $track->getTitle();

        if ($carrierCode !== 'CUSTOM') {
            return $carrierCode;
        }

        $code = $this->findCodeByTitle($carrierTitle, $this->getSupplementaryCarriersMap());
        if ($code) {
            return $code;
        }
        $code = $this->findCodeByTitle($carrierTitle, $this->getFbSupportedShippingCarriers());
        if ($code) {
            return $code;
        }

        return 'OTHER';
    }

    /**
     * Get carrier code for facebook
     *
     * @param Track $track
     * @return string
     */
    public function getCarrierCodeForFacebook($track)
    {
        $supportedCarriers = $this->getFbSupportedShippingCarriers();
        $canonicalCarrierCode = $this->getCanonicalCarrierCode($track);

        return array_key_exists($canonicalCarrierCode, $supportedCarriers) ? $canonicalCarrierCode : 'OTHER';
    }
}
