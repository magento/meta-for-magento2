<?php
declare(strict_types=1);

namespace Meta\Conversion\Block\Pixel;

/**
 * @api
 */
class CustomerRegistrationSuccess extends Common
{
    /**
     * Returns content type
     *
     * @return string
     */
    public function getContentType()
    {
        return "customer_registration";
    }

    /**
     * Returns event name
     *
     * @return string
     */
    public function getEventToObserveName()
    {
        return 'facebook_businessextension_ssapi_customer_registration_success';
    }
}
