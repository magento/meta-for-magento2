<?php

declare(strict_types=1);

namespace Meta\Sales\Controller\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteRepository;
use Meta\BusinessExtension\Helper\FBEHelper;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;

/**
 * Controller for loading cart based on masked ID.
 */
class LoadMetaCart implements HttpGetActionInterface
{
    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * @var Authenticator
     */
    private $authenticator;

    /**
     * Constructor.
     *
     * @param RequestInterface $request
     * @param RedirectFactory $resultRedirectFactory
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param QuoteRepository $quoteRepository
     * @param CheckoutSession $checkoutSession
     * @param FBEHelper $fbeHelper
     * @param Authenticator $authenticator
     */
    public function __construct(
        RequestInterface $request,
        RedirectFactory $resultRedirectFactory,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        QuoteRepository $quoteRepository,
        CheckoutSession $checkoutSession,
        FBEHelper $fbeHelper,
        Authenticator $authenticator
    ) {
        $this->request = $request;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->fbeHelper = $fbeHelper;
        $this->authenticator = $authenticator;
    }

    /**
     * Execute action based on request.
     *
     * IMPORTANT: Signatures must be URL-Encoded after being Base64 Encoded, or verification will fail.
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        try {

            $cartId = $this->request->getParam('cart_id');
            $signature = $this->request->getParam('signature');

            if (!$this->authenticator->verifySignature($cartId, $signature)) {
                $this->fbeHelper->log(
                    "RSA Verification Failed for cartId: {$cartId} with signature: {$signature}"
                );
                throw new LocalizedException(__('RSA Signature Validation Failed'));
            }

            $quoteId = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id')->getQuoteId();
            $quote = $this->quoteRepository->get($quoteId);
            $this->checkoutSession->replaceQuote($quote);
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->fbeHelper->logExceptionImmediatelyToMeta(
                $e,
                [
                    'event' => 'guest_cart_link',
                    'event_type' => 'cart_redirect',
                    'extra_data' => [
                        'signature' => $signature,
                        'cart_id' => $cartId,
                    ],
                ]
            );
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        }
    }
}
