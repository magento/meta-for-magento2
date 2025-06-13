define([
    'jquery',
    'Meta_Conversion/js/metaPixelTracker'
], function ($, metaPixelTracker) {
    'use strict';

    let productName, sku, productId, price, payload;

    return function (config) {

        function callMetaPixelTracker() {
            if (config !== null) {
                // browser event payload
                config.browserEventData.payload.content_name = payload.productName;
                config.browserEventData.payload.content_ids = [payload.sku];
                config.browserEventData.payload.content_type = 'product_group';

                // server event payload
                config.payload.content_name = payload.productName;
                config.payload.content_ids = [payload.sku];
                config.payload.content_type = 'product_group';

                metaPixelTracker(config);
            }
        }

        function _getPrice(element) {
            // For Swatch and Text Type - PLP page
            productId = $(element).parents('.product-item-details').find('.price-final_price').data('product-id');
            // for DropdownType, Swatch and Text Type - PDP page
            if (!productId) {
                productId = $(element).parents('.product-info-main').find('.price-final_price').data('product-id');
            }
            price = $('#product-price-' + productId).data('price-amount');
            if(!price) {
                // get product price based on the catalog price display type (including/excluding tax)
                price = $('#price-excluding-tax-product-price-' + productId).data('price-amount');
            }
            return price.toFixed(2);
        }

        function _getProductName(element) {
            // For Swatch and Text Type - PLP page
            productName = $(element).parents('.product-item-details').find('.product-item-link').text();
            productName = productName.trim();
            // for DropdownType, Swatch and Text Type - PDP page
            if (!productName) {
                productName = $(element).parents('.product-info-main').find('.page-title .base').text();
                productName = productName.trim();
            }
            return productName;
        }

        function _getSku(element) {
            // For Swatch and Text Type - PLP page
            sku = $(element).parents('li.product-item').find('form').data('product-sku');
            // for Swatch and Text Type - PDP page
            if (!sku) {
                sku = $(element).parents('.product-info-main').find('.product.attribute.sku .value').text();
                sku = sku.trim();
            }
            return sku;
        }

        function setPayload(element) {
            payload = {
                'productName': _getProductName(element),
                'sku': _getSku(element)
            };
            config.browserEventData.payload.value = _getPrice(element);
            config.payload.value = _getPrice(element);
        }

        // Dropdown event for pdp
        $('.super-attribute-select').on('change', function () {
            setPayload(this);
            callMetaPixelTracker();
        });

        // swatch click event from everywhere
        $('[class*="swatch-opt"]').on('click', '.swatch-option', function () {
            setPayload(this);
            callMetaPixelTracker();
        });
    };
});
