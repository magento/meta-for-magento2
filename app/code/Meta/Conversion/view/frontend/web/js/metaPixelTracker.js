define([
    'jquery'
], function ($) {
    'use strict';

    return function (config) {

        var browserEventData = config.browserEventData;

        $.ajax({
            showLoader: true,
            url: config.url,
            type: "POST",
            data: config.payload,
            dataType: "json",
            success: function (response) {
                if (response.eventId) {
                    let browserPayload = response.payload;
                    browserPayload.source = browserEventData.source;
                    browserPayload.pluginVersion = browserEventData.pluginVersion;

                    fbq('set', 'agent', browserEventData.fbAgentVersion, browserEventData.fbPixelId);
                    fbq(browserEventData.track, browserEventData.event, browserPayload, {
                            eventID: response.eventId
                        }
                    );
                }
            },
            error: function (error) {
                console.log(error)
            }

        });
    }
});
