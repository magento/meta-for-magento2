<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Pixel;

class Search extends Common
{
    /**
     * @return string
     */
    public function getSearchQuery()
    {
        return htmlspecialchars(
            $this->getRequest()->getParam('q'),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    /**
     * @return string
     */
    public function getEventToObserveName()
    {
        return 'facebook_businessextension_ssapi_search';
    }
}
