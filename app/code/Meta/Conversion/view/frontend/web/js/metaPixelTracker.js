/* global fbq */
define([
    'jquery'
], function ($) {
    'use strict';
    function generateUUID() {
        if(crypto.randomUUID) {
            return crypto.randomUUID();
        }
        // crypto.randomUUID() was added to chrome in late 2021. This is a passable polyfill.
        const buf = new Uint8Array(16);
        crypto.getRandomValues(buf);
        buf[6] = (buf[6] & 0x0f) | 0x40; // set version to 0100 (UUID version 4)
        buf[8] = (buf[8] & 0x3f) | 0x80; // set variant to 10 (RFC4122 variant)
        return Array.from(buf).map((b, i) => {
            const s = b.toString(16).padStart(2, '0');
            return (i === 4 || i === 6 || i === 8 || i === 10) ? '-' + s : s;
        }).join('');
    }

    return function (config) {
        var browserEventData = config.browserEventData,
            eventId = generateUUID();

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
            global: false,
            error: function (error) {
                console.log(error);
            }
        });
    };
});
