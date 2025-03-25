/* global fbq */
define([
    'jquery',
    'Meta_Conversion/js/tracking'
], function ($, cookies) {
    'use strict';
    function generateUUID() {
        if (crypto.randomUUID) {
            return crypto.randomUUID();
        }
        // crypto.randomUUID() was added to chrome in late 2021. This is a passable polyfill.
        const buf = new Uint8Array(16);

        crypto.getRandomValues(buf);
        buf[6] = buf[6] & 0x0f | 0x40; // set version to 0100 (UUID version 4)
        buf[8] = buf[8] & 0x3f | 0x80; // set to 10 (RFC4122)
        return Array.from(buf).map((b, i) => {
            const s = b.toString(16).padStart(2, '0'),
              isUuidOffsetChar = i === 4 || i === 6 || i === 8 || i === 10;

            return isUuidOffsetChar ? '-' + s : s;
        }).join('');
    }

    function trackPixelEvent(config) {
        const pixelId = config.browserEventData.fbPixelId,
            agent = config.browserEventData.fbAgentVersion,
            track = config.browserEventData.track,
            event = config.browserEventData.event,
            pixelEventPayload = config.browserEventData.payload,
            eventId = config.payload.eventId,
            trackServerEventUrl = config.url,
            serverEventPayload = config.payload;

        fbq('set', 'agent', agent, pixelId);
        fbq(track, event, pixelEventPayload, {
            eventID: eventId
        });
    }

    return function (config) {
      if (cookies.getCookie(config.payload.eventName)) {
        config.payload.eventId = cookies.getCookie(config.payload.eventName);
      }else {
        config.payload.eventId = generateUUID();
      }
        config.browserEventData.payload.source = config.browserEventData.source;
        config.browserEventData.payload.pluginVersion = config.browserEventData.pluginVersion;

        if (window.metaPixelInitFlag) {
            trackPixelEvent(config);
        } else {
            // wait until pixel is initialized
            window.addEventListener('metaPixelInitialized', () => {
                trackPixelEvent(config);
            }, {once: true});
        }
    };
});
