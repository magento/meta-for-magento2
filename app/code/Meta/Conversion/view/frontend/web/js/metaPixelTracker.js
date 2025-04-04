/* global fbq */
define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
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
            eventId = config.payload.eventId;

        fbq('set', 'agent', agent, pixelId);
        fbq(track, event, pixelEventPayload, {
            eventID: eventId
        });
    }

    return function (config) {
        if (!config.payload.eventId) {
          console.log('we dont have event id in payload');
          let eventIdsFromSection = customerData.get('capi-event-ids')();
          if (eventIdsFromSection['eventIds'][config.payload.eventName]) {
            console.log('event if for '+config.payload.eventName+' is '+eventIdsFromSection['eventIds'][config.payload.eventName]);
            config.payload.eventId = eventIdsFromSection['eventIds'][config.payload.eventName];
          }
        }
        if (!config.payload.eventId) {
          console.log('still no event id in payload, generating randomnly');
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
