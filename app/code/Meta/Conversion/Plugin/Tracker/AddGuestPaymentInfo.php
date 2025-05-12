<?php
declare(strict_types=1);

namespace Meta\Conversion\Plugin\Tracker;

use Meta\Conversion\Helper\MagentoDataHelper;
use Meta\Conversion\Model\CapiTracker;
use Meta\Conversion\Model\Tracker\AddPaymentInfo as AddPaymentInfoTracker;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\GuestPaymentInformationManagement;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

class AddGuestPaymentInfo
{

    const EVENT_NAME = 'facebook_businessextension_ssapi_add_payment_info';

    public function __construct(
        private readonly AddPaymentInfoTracker $addPaymentInfoTracker,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly MagentoDataHelper $magentoDataHelper,
        private readonly CapiTracker $capiTracker,
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory
    ) { }

    public function afterSavePaymentInformation(
        GuestPaymentInformationManagement $paymentInformationManagement,
        bool $result,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        if ($result) {
            $payload = $this->addPaymentInfoTracker->getPayload($this->getInitialPayload($cartId));
            $this->capiTracker->execute($payload, self::EVENT_NAME, $this->addPaymentInfoTracker->getEventType(), true);
        }
        return $result;
    }

    /**
     * @param $cartId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getInitialPayload($cartId): array
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quote = $this->cartRepository->getActive($quoteIdMask->getQuoteId());
        $payload = $this->magentoDataHelper->getCartPayload($quote);
        $payload['content_type'] = 'product';
        return $payload;
    }
}
