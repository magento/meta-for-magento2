<?php
declare(strict_types=1);

namespace Meta\Conversion\Block\Pixel;

/**
 * @api
 */
class AddPaymentInfo extends Common
{
    /**
     * Get event name
     *
     * @return string
     */
    public function getEventToObserveName()
    {
        return 'facebook_businessextension_ssapi_add_payment_info';
    }
}
