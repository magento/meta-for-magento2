define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {

        $.ajax({
            showLoader: true,
            url: config.url,
            type: "POST",
            data: config.payload,
            dataType: "json",
            success: function (response) {
                console.log(response)
            },
            error: function (error) {
                console.log(error)
            }
            
        });
    }
});
