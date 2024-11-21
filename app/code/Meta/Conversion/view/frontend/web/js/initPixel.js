/* global fbq */
define([
    'jquery'
], function ($) {
    'use strict';
    return function (config) {
        const pixelId = config.pixelId;
        const automaticMatchingFlag = config.automaticMatchingFlag;
        const userDataUrl = config.userDataUrl;
        const agent = config.agent;
        const metaPixelInitializedEvent = new Event('metaPixelInitialized');

        window.metaPixelInitFlag = false;

        if (!window.fbq) {
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
                n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
                document,'script','//connect.facebook.net/en_US/fbevents.js');
        }

        //init pixel with empty user data
        fbq(
            'init',
            pixelId,
            {},
            {agent: agent}
        );

        if (!automaticMatchingFlag) {
            window.metaPixelInitFlag = true;
            window.dispatchEvent(metaPixelInitializedEvent);
            return;
        }

        // update pixel with user data if automatic matching is enabled and user data is available
        $.get({
            url: userDataUrl,
            dataType: 'json',
            success: function (res) {
                if (res.success && res.user_data) {
                    fbq(
                        'init',
                        pixelId,
                        res.user_data,
                        {agent: agent}
                    );
                }
            },
            error: function (error) {
                console.log(error);
            }
        }).always(function() {
            window.metaPixelInitFlag = true;
            window.dispatchEvent(metaPixelInitializedEvent);
        });
    };
});
