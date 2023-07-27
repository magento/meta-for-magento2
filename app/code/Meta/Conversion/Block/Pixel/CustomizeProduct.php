<?php
declare(strict_types=1);

namespace Meta\Conversion\Block\Pixel;

/**
 * @api
 */
class CustomizeProduct extends Common
{
    /**
     * Get event name
     *
     * @return string
     */
    public function getEventToObserveName()
    {
        return 'facebook_businessextension_ssapi_customize_product';
    }
}
