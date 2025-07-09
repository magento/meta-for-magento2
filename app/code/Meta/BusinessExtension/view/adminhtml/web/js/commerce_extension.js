/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

'use strict';

require(['jquery'], function (jQuery) {

    const ajaxify = function (url) {
        return url + '?isAjax=true&storeId=' + window.facebookBusinessExtensionConfig.storeId;
    };

    const ajaxParam = function (params) {
        if (window.FORM_KEY) {
            params.form_key = window.FORM_KEY;
        }
        return params;
    };

    function parseURL(url)
    {
        const parser = document.createElement('a');
        parser.href = url;
        return parser;
    }

    function urlFromSameDomain(url1, url2)
    {
        const u1 = parseURL(url1);
        const u2 = parseURL(url2);
        const u1host = u1.host.replace('web.', 'www.');
        const u2host = u2.host.replace('web.', 'www.');
        return u1.protocol === u2.protocol && u1host === u2host;
    }

    function deleteFBAssetsAndReloadPage()
    {
        const _this = this;
        jQuery.ajax({
            type: 'delete',
            url: ajaxify(window.facebookBusinessExtensionConfig.deleteConfigKeys),
            data: ajaxParam({
                storeId: window.facebookBusinessExtensionConfig.storeId,
            }),
            success: function onSuccess(data, _textStatus, _jqXHR)
            {
                let msg = '';
                if (data.success) {
                    msg = data.message;
                } else {
                    msg = data.error_message;
                }
                cleanConfigCacheAndReloadPage();
            },
            error: function () {
                console.error('There was a problem deleting the connection, Please try again.');
            }
        });
    }

    function updateMBEConfigAndReloadPage(triggerPostOnboarding)
    {
        jQuery.ajax({
            type: 'post',
            url: ajaxify(window.facebookBusinessExtensionConfig.updateInstalledMBEConfig),
            data: ajaxParam({
                storeId: window.facebookBusinessExtensionConfig.storeId,
                triggerPostOnboarding: triggerPostOnboarding
            }),
            success: function onSuccess(data, _textStatus, _jqXHR)
            {
                let msg;
                if (data.success) {
                    msg = data.message;
                    console.log("Update success");
                    if (triggerPostOnboarding) {
                        // Store sync info before reload
                        sessionStorage.setItem('mbe_pending_post_onboarding_sync', JSON.stringify({
                            storeId: window.facebookBusinessExtensionConfig.storeId,
                            timestamp: new Date().getTime()
                        }));
                    }
                } else {
                    msg = data.error_message;
                }
                cleanConfigCacheAndReloadPage();
            },
            error: function () {
                console.error('There was a problem updating the installed MBE config');
            }
        });
    }

    // Add function to handle post-onboarding sync
    function handlePostReloadSync() {
        const pendingSyncData = sessionStorage.getItem('mbe_pending_post_onboarding_sync');
        if (pendingSyncData) {
            sessionStorage.removeItem('mbe_pending_post_onboarding_sync');
            try {
                const {storeId, timestamp} = JSON.parse(pendingSyncData);
                const SYNC_EXPIRY_MS = 30 * 60 * 1000; // 30 minutes
                if (new Date().getTime() - timestamp < SYNC_EXPIRY_MS) {
                    console.log('Starting initial sync in the background');
                    // Use setTimeout to ensure page is fully loaded
                    setTimeout(function () {
                        jQuery.ajax({
                            type: 'post',
                            url: ajaxify(window.facebookBusinessExtensionConfig.postFBEOnboardingSync),
                            data: ajaxParam({
                                storeId: storeId
                            }),
                            success: function (response) {
                                console.log('Background sync completed successfully');
                            },
                            error: function (error) {
                                console.error('Background sync failed:', error);
                            }
                        });
                    }, 0);
                }
            } catch (e) {
                console.error('Error processing pending sync data:', e);
            }
        }
    }

    // Initialize post-reload sync handling
    jQuery(document).ready(handlePostReloadSync);

    function cleanConfigCacheAndReloadPage()
    {
        jQuery.ajax({
            type: 'post',
            url: ajaxify(window.facebookBusinessExtensionConfig.cleanConfigCacheUrl),
            data: ajaxParam({}),
            success: function onSuccess(data, _textStatus, _jqXHR)
            {
                if (data.success) {
                    window.location.reload();
                }
            },
            error: function () {
                console.error('There was a problem cleaning config cache');
            }
        });
    }

    function handleCommerceExtensionDeletion(message)
    {
        const success = message.success;
        if (success) {
            const messageEvent = message.event;
            if (messageEvent === 'CommerceExtension::UNINSTALL') {
                // Delete asset ids stored in db instance.
                deleteFBAssetsAndReloadPage();
            }
        }
    }

    function handleResizeEvent(message)
    {
        if (message.event !== 'CommerceExtension::RESIZE') {
            return;
        }

        const {height} = message;
        document.getElementById('commerce-extension-iframe').height = height;
    }

    function handleUpdateMBEConfigEvent(message)
    {
        if (message.event === 'CommerceExtension::UPDATE_AND_COMPLETE') {
            updateMBEConfigAndReloadPage(true);
        }

        if (message.event === 'CommerceExtension::UPDATE') {
            updateMBEConfigAndReloadPage(false);
        }
    }


    function listenForCommerceExtensionMessage(event)
    {
        const origin = event.origin || event.originalEvent.origin;
        const commerceExtensionOrigin = document.getElementById("commerce-extension-iframe").src;
        if (urlFromSameDomain(origin, new URL(commerceExtensionOrigin).origin)) {
            const message = event.data;
            if (message != null) {
                handleCommerceExtensionDeletion(message);
                handleResizeEvent(message);
                handleUpdateMBEConfigEvent(message)
            }
        }
    }

    function repairCommercePartnerIntegration()
    {
        jQuery.ajax({
            type: 'post',
            url: ajaxify(window.facebookBusinessExtensionConfig.repairRepairCommercePartnerIntegrationUrl),
            data: ajaxParam({
                storeId: window.facebookBusinessExtensionConfig.storeId,
            }),
            success: function onSuccess(_data)
            {
            },
            error: function () {
                console.error('There was error repairing the Meta Commerce Partner Integration');
            }
        });
    }

    const commerceIframe = document.getElementById("commerce-extension-iframe");
    if (commerceIframe != null) {
        window.addEventListener('message', listenForCommerceExtensionMessage);
        repairCommercePartnerIntegration();
    }

    const resetLink = document.getElementById('commerce-extension-reset-link');
    if (resetLink != null) {
        resetLink.addEventListener('click', function () {
            const confirmationText = "Are you sure you want to delete the connection?\n\n" +
                'Your store will no longer be connected to Meta and you will need to reconnect your ' +
                'assets to restore the connection.';
            if (!confirm(confirmationText)) {
                return;
            }

            deleteFBAssetsAndReloadPage();
            return false;
        });
    }

});