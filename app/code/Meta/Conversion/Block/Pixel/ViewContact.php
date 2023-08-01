<?php
declare(strict_types=1);

namespace Meta\Conversion\Block\Pixel;

/**
 * @api
 */
class ViewContact extends Common
{
    /**
     * Returns content type
     *
     * @return string
     */
    public function getContentType()
    {
        return "contact";
    }

    /**
     * Returns event name
     *
     * @return string
     */
    public function getEventToObserveName()
    {
        return 'facebook_businessextension_ssapi_contact';
    }
}
