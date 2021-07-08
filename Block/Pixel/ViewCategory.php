<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Block\Pixel;

class ViewCategory extends Common
{
    /**
     * @return string|null
     */
    public function getCategory()
    {
        $category = $this->registry->registry('current_category');
        return $category ? $this->escapeQuotes($category->getName()) : null;
    }

    /**
     * @return string
     */
    public function getEventToObserveName()
    {
        return 'facebook_businessextension_ssapi_view_category';
    }
}
