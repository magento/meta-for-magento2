var jQuery = (function (jQuery) {
    if (jQuery && typeof jQuery === 'function') {
        return jQuery;
    } else {
        console.error('window.jQuery is not valid or loaded, please check your magento 2 installation!');
        // if jQuery is not there, we return a dummy jQuery object with ajax,
        // so it will not break our following code
        return {
            ajax: function () {
            }
        };
    }
})(window.jQuery);
var ajaxify = function (url) {
    return url + '?isAjax=true&storeId=' + window.facebookBusinessExtensionConfig.storeId;
};

var ajaxParam = function (params) {
    if (window.FORM_KEY) {
        params.form_key = window.FORM_KEY;
    }
    return params;
};

function parseURL(url) {
    var parser = document.createElement('a');
    parser.href = url;
    return parser;
}

function urlFromSameDomain(url1, url2) {
    var u1 = parseURL(url1);
    var u2 = parseURL(url2);
    var u1host = u1.host.replace('web.', 'www.');
    var u2host = u2.host.replace('web.', 'www.');
    return u1.protocol === u2.protocol && u1host === u2host;
}

function deleteFBAssets() {
    var _this = this;
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
            cleanConfigCache();
        },
        error: function () {
            console.error('There was a problem deleting the connection, Please try again.');
        }
    });
}

function cleanConfigCache() {
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
    var success = message.success;
    if (success) {
        const messageEvent = message.event;
        if (messageEvent === 'CommerceExtension::UNINSTALL') {
            // Delete asset ids stored in db instance.
            deleteFBAssets();
        }
    }
}

function handleResizeEvent(message) {
    if (message.event !== 'CommerceExtension::RESIZE') {
        return;
    }

    var {height} = message;
    document.getElementById('commerce-extension-iframe').height = height;
}

function listenForCommerceExtensionMessage(event) {
    var origin = event.origin || event.originalEvent.origin;
    var commerceExtensionOrigin = document.getElementById("commerce-extension-iframe").src;
    if (urlFromSameDomain(origin, new URL(commerceExtensionOrigin).origin)) {
        var message = event.data;
        if (message != null) {
            handleCommerceExtensionDeletion(message);
            handleResizeEvent(message);
        }
    }
}

(function main() {
    window.addEventListener('message', listenForCommerceExtensionMessage);
})();
