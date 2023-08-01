define([
  'Meta_Conversion/js/tracking',
  'Meta_Conversion/js/metaPixelTracker'
], function (cookies, metaPixelTracker) {
  'use strict';

  return function (metaPixelData) {
    let payload,
        cookieName = 'event_contact';

    payload = cookies.getCookie(cookieName);
    if (payload !== null) {
      metaPixelTracker(metaPixelData);
      cookies.delCookie(cookieName);
    }
  };
});
