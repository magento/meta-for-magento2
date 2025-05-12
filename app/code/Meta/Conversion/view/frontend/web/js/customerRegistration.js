define([
    'Meta_Conversion/js/tracking',
    'Meta_Conversion/js/metaPixelTracker'
], function (cookies, metaPixelTracker) {
  'use strict';

  return function (metaPixelData) {
    let payload = null,
      eventName = '',
      currency = '',
      cookieName = 'event_customer_register';

    eventName = metaPixelData.payload.eventName;
    currency = metaPixelData.browserEventData.payload.currency;

    function isPayloadAvailable()
    {
      payload = cookies.getCookie(cookieName);

      if (payload !== null) {
        payload = cookies.parseJson(payload);
        return true;
      }
      return false;
    }

    function prepareServerPayload()
    {
      //eventName vanish here. So again need to reassign eventName to server payload
      metaPixelData.payload = metaPixelData.browserEventData.payload;
      metaPixelData.payload.eventName = eventName;
      metaPixelData.payload.currency = currency;
    }

    function prepareBrowserPayload()
    {
      metaPixelData.browserEventData.payload = payload;
      metaPixelData.browserEventData.payload.currency = currency;
    }

    if (isPayloadAvailable()) {
      //Prepare Browser Payload
      prepareBrowserPayload();

      //Prepare Server Payload
      prepareServerPayload();

      //Call to metaTracker
      metaPixelTracker(metaPixelData);
      cookies.delCookie(cookieName);
    }
  };
});
