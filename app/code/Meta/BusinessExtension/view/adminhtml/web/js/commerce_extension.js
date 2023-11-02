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

    function parseURL(url) {
        const parser = document.createElement('a');
        parser.href = url;
        return parser;
    }

    function urlFromSameDomain(url1, url2) {
        const u1 = parseURL(url1);
        const u2 = parseURL(url2);
        const u1host = u1.host.replace('web.', 'www.');
        const u2host = u2.host.replace('web.', 'www.');
        return u1.protocol === u2.protocol && u1host === u2host;
    }

    function deleteFBAssetsAndReloadPage() {
        const _this = this;
        jQuery.ajax({
            type: 'delete',
            url: ajaxify(window.facebookBusinessExtensionConfig.deleteConfigKeys),
            data: ajaxParam({
                storeId: window.facebookBusinessExtensionConfig.storeId,
            }),
            success: function onSuccess(data, _textStatus, _jqXHR) {
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

    function cleanConfigCacheAndReloadPage() {
        jQuery.ajax({
            type: 'post',
            url: ajaxify(window.facebookBusinessExtensionConfig.cleanConfigCacheUrl),
            data: ajaxParam({}),
            success: function onSuccess(data, _textStatus, _jqXHR) {
                if (data.success) {
                    window.location.reload();
                }
            },
            error: function () {
                console.error('There was a problem cleaning config cache');
            }
        });
    }

    function handleCommerceExtensionDeletion(message) {
        const success = message.success;
        if (success) {
            const messageEvent = message.event;
            if (messageEvent === 'CommerceExtension::UNINSTALL') {
                // Delete asset ids stored in db instance.
                deleteFBAssetsAndReloadPage();
            }
        }
    }

    function handleResizeEvent(message) {
        if (message.event !== 'CommerceExtension::RESIZE') {
            return;
        }

        const {height} = message;
        document.getElementById('commerce-extension-iframe').height = height;
    }

    function listenForCommerceExtensionMessage(event) {
        const origin = event.origin || event.originalEvent.origin;
        const commerceExtensionOrigin = document.getElementById("commerce-extension-iframe").src;
        if (urlFromSameDomain(origin, new URL(commerceExtensionOrigin).origin)) {
            const message = event.data;
            if (message != null) {
                handleCommerceExtensionDeletion(message);
                handleResizeEvent(message);
            }
        }
    }

    const commerceIframe = document.getElementById("commerce-extension-iframe");
    if (commerceIframe != null) {
        window.addEventListener('message', listenForCommerceExtensionMessage);
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