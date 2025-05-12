<?php
declare(strict_types=1);

namespace Meta\Conversion\Block\Pixel;

/**
 * @api
 */
class AddToWishlist extends Common
{
    /**
     * Returns event name
     *
     * @return string
     */
    public function getEventToObserveName()
    {
        return 'facebook_businessextension_ssapi_add_to_wishlist';
    }
}
