define([
    'jquery'
], function ($) {
    'use strict';

    return function (config) {

        var browserEventData = config.browserEventData;
        var eventId = crypto.randomUUID();

        config.payload.eventId = eventId;

        $.ajax({
            showLoader: true,
            url: config.url,
            type: "POST",
            data: config.payload,
            dataType: "json",
            success: function (response) {
                let browserPayload = response.payload;
                browserPayload.source = browserEventData.source;
                browserPayload.pluginVersion = browserEventData.pluginVersion;

                fbq('set', 'agent', browserEventData.fbAgentVersion, browserEventData.fbPixelId);
                fbq(browserEventData.track, browserEventData.event, browserPayload, {
                        eventID: eventId
                    }
                );
            },
            error: function (error) {
                console.log(error)
            }

        });
    }
});
