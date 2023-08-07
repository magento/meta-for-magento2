define([
  'Meta_Conversion/js/tracking',
  'Meta_Conversion/js/metaPixelTracker'
], function (cookies, metaPixelTracker) {
  'use strict';

  return function (metaPixelData) {
    let payload = null,
      cookieName = 'event_add_to_wishlist',
      currency = '',
      eventName = '';

    currency = metaPixelData.browserEventData.payload.currency;
    eventName = metaPixelData.payload.eventName;

    function isPayloadAvailable()
    {
      payload = cookies.getCookie(cookieName);
      if (payload !== null) {
        payload = cookies.parseJson(payload);
        return true;
      }
      return false;
    }

    if (isPayloadAvailable()) {
      metaPixelData.browserEventData.payload = payload;
      metaPixelData.browserEventData.payload.currency = currency;

      metaPixelData.payload = payload;
      metaPixelData.payload.currency = currency;
      metaPixelData.payload.eventName = eventName;

      metaPixelTracker(metaPixelData);
      cookies.delCookie(cookieName);
    }
  };
});
