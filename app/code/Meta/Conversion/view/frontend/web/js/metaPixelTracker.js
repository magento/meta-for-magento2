/* global fbq */
define([
    'jquery'
], function ($) {
    'use strict';

    return function (config) {
        var browserEventData = config.browserEventData,
            eventId = crypto.randomUUID();

        config.payload.eventId = eventId;

        let browserPayload = config.browserEventData.payload;

        browserPayload.source = browserEventData.source;

        browserPayload.pluginVersion = browserEventData.pluginVersion;

        fbq('set', 'agent', browserEventData.fbAgentVersion, browserEventData.fbPixelId);
        fbq(browserEventData.track, browserEventData.event, browserPayload, {
                eventID: eventId
            }
        );

        $.ajax({
            showLoader: true,
            url: config.url,
            type: 'POST',
            data: config.payload,
            dataType: 'json',
            error: function (error) {
                console.log(error);
            }
        });
    };
});
