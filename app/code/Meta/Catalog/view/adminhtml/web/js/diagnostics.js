require([
    'jquery'
], function ($) {
    'use strict';

    $(document).on('ready', function () {
        let storeId = $('.store').val();

        $('.store-' + storeId).show();
    });

    $('.store').on('change', function () {
        let storeId = $(this).val();

        $('.store-info').hide();
        $('.store-' + storeId).show();
    });
});
