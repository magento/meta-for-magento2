define([
    'jquery'
], function ($) {
    'use strict';

    return function (config) {
        var eventId = window.eventId;
        if (eventId) {
            config.payload.eventId = eventId
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
    }
});
