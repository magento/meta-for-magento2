/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */
'use strict';

const React = require('./react');
const ReactDOM = require('./react-dom');
const FBUtils = require('./utils');

const jQuery = (function (jQuery) {
    if (jQuery && typeof jQuery === 'function') {
        return jQuery;
    } else {
        console.error('window.jQuery is not valid or loaded, please check your magento 2 installation!');
        // if jQuery is not there, we return a dummy jQuery obejct with ajax,
        // so it will not break our following code
        return {
            ajax: function () {
            }
        };
    }
})(window.jQuery);

const ajaxify = function (url) {
    return url + '?isAjax=true&storeId=' + window.facebookBusinessExtensionConfig.storeId;
};

const getAndEncodeExternalClientMetadata = function () {
    const metaData = {
        customer_token: window.facebookBusinessExtensionConfig.customApiKey,
        commerce_partner_seller_platform_type: window.facebookBusinessExtensionConfig.commerce_partner_seller_platform_type
    };
    return encodeURIComponent(JSON.stringify(metaData));
}

const ajaxParam = function (params) {
    if (window.FORM_KEY) {
        params.form_key = window.FORM_KEY;
    }
    return params;
};

jQuery(document).ready(function () {
    const FBEFlowContainer = React.createClass({

        getDefaultProps: function () {
            return {
                installed: window.facebookBusinessExtensionConfig.installed
            };
        },
        getInitialState: function () {
            return {installed: this.props.installed};
        },

        bindMessageEvents: function bindMessageEvents() {
            const _this = this;

            window.addEventListener('message', function (event) {
                const origin = event.origin || event.originalEvent.origin;
                if (FBUtils.urlFromSameDomain(origin, window.facebookBusinessExtensionConfig.popupOrigin)) {
                    const message = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
                    if (!message) {
                        return;
                    }

                    _this.handleMessage(message);
                }
            }, false);
        },
        handleMessage: function handleMessage(message) {
            const _this = this;

            // "FBE Iframe" uses the 'action' field in its messages.
            // "Commerce Extension" uses the 'type' field in its messages.
            const action = message.action;
            const messageEvent = message.event;

            if (action === 'delete' || messageEvent === 'CommerceExtension::UNINSTALL') {
                // Delete asset ids stored in db instance.
                _this.deleteFBAssets();
            }

            if (action === 'create' || messageEvent === 'CommerceExtension::INSTALL') {
                const success = message.success;
                if (success) {
                    const accessToken = message.access_token;
                    const pixelId = message.pixel_id;
                    const profiles = message.profiles;
                    const catalogId = message.catalog_id;
                    const commercePartnerIntegrationId = message.commerce_partner_integration_id;
                    const pageId = message.page_id;
                    const installedFeatures = message.installed_features;

                    _this.savePixelId(pixelId);
                    _this.saveAccessToken(accessToken);
                    _this.saveProfilesData(profiles);
                    _this.saveAAMSettings(pixelId);
                    _this.saveConfig(accessToken, catalogId, pageId, commercePartnerIntegrationId);
                    _this.saveInstalledFeatures(installedFeatures);
                    _this.cleanConfigCache();
                    _this.postFBEOnboardingSync();

                    if (window.facebookBusinessExtensionConfig.isCommerceExtensionEnabled) {
                        window.location.reload();
                    } else {
                        _this.setState({installed: 'true'});
                    }
                }
            }

            if (messageEvent === 'CommerceExtension::RESIZE') {
                const {height} = message;
                document.getElementById('fbe-iframe').height = height;
            }
        },
        savePixelId: function savePixelId(pixelId) {
            const _this = this;
            if (!pixelId) {
                console.error('Meta Business Extension Error: got no pixel_id');
                return;
            }
            jQuery.ajax({
                type: 'post',
                url: ajaxify(window.facebookBusinessExtensionConfig.setPixelId),
                async: false,
                data: ajaxParam({
                    pixelId: pixelId,
                    storeId: window.facebookBusinessExtensionConfig.storeId,
                }),
                success: function onSuccess(data, _textStatus, _jqXHR) {
                    const response = data;
                    let msg = '';
                    if (response.success) {
                        msg = "The Meta Pixel with ID: " + response.pixelId + " is now installed on your website.";
                    } else {
                        msg = "There was a problem saving the pixel. Please try again";
                    }
                    _this.consoleLog(msg);
                },
                error: function () {
                    console.error('There was a problem saving the pixel with id', pixelId);
                }
            });
        },
        saveAccessToken: function saveAccessToken(accessToken) {
            const _this = this;
            if (!accessToken) {
                console.error('Meta Business Extension Error: got no access token');
                return;
            }
            jQuery.ajax({
                type: 'post',
                url: ajaxify(window.facebookBusinessExtensionConfig.setAccessToken),
                async: false,
                data: ajaxParam({
                    accessToken: accessToken,
                }),
                success: function onSuccess(data, _textStatus, _jqXHR) {
                    _this.consoleLog('Access token saved successfully');
                },
                error: function () {
                    console.error('There was an error saving access token');
                }
            });
        },
        saveProfilesData: function saveProfilesData(profiles) {
            const _this = this;
            if (!profiles) {
                console.error('Meta Business Extension Error: got no profiles data');
                return;
            }
            jQuery.ajax({
                type: 'post',
                url: ajaxify(window.facebookBusinessExtensionConfig.setProfilesData),
                async: false,
                data: ajaxParam({
                    profiles: JSON.stringify(profiles),
                }),
                success: function onSuccess(data, _textStatus, _jqXHR) {
                    _this.consoleLog('set profiles data ' + data.profiles);
                },
                error: function () {
                    console.error('There was problem saving profiles data', profiles);
                }
            });
        },
        saveAAMSettings: function saveAAMSettings(pixelId) {
            const _this = this;
            jQuery.ajax({
                'type': 'post',
                url: ajaxify(window.facebookBusinessExtensionConfig.setAAMSettings),
                async: false,
                data: ajaxParam({
                    pixelId: pixelId,
                }),
                success: function onSuccess(data, _textStatus, _jqXHR) {
                    if (data.success) {
                        _this.consoleLog('AAM settings successfully saved ' + data.settings);
                    } else {
                        _this.consoleLog('AAM settings could not be read for the given pixel');
                    }
                },
                error: function () {
                    _this.consoleLog('There was an error retrieving AAM settings');
                }
            });
        },
        saveInstalledFeatures: function saveInstalledFeatures(installedFeatures) {
            const _this = this;
            if (!installedFeatures) {
                console.error('Meta Business Extension Error: got no installed_features data');
                return;
            }
            jQuery.ajax({
                type: 'post',
                url: ajaxify(window.facebookBusinessExtensionConfig.setInstalledFeatures),
                async: false,
                data: ajaxParam({
                    installed_features: JSON.stringify(installedFeatures),
                }),
                success: function onSuccess(data, _textStatus, _jqXHR) {
                    if (data.success) {
                        _this.consoleLog('Saved installed_features data', data);
                    } else {
                        console.error('There was problem saving installed_features data', installedFeatures);
                    }
                },
                error: function () {
                    console.error('There was problem saving installed_features data', installedFeatures);
                }
            });
        },
        cleanConfigCache: function cleanConfigCache() {
            const _this = this;
            jQuery.ajax({
                type: 'post',
                url: ajaxify(window.facebookBusinessExtensionConfig.cleanConfigCacheUrl),
                async: false,
                data: ajaxParam({}),
                success: function onSuccess(data, _textStatus, _jqXHR) {
                    if (data.success) {
                        _this.consoleLog('Config cache successfully cleaned');
                    }
                },
                error: function () {
                    console.error('There was a problem cleaning config cache');
                }
            });
        },
        saveConfig: function saveConfig(accessToken, catalogId, pageId, commercePartnerIntegrationId) {
            const _this = this;
            jQuery.ajax({
                type: 'post',
                url: ajaxify(window.facebookBusinessExtensionConfig.saveConfig),
                async: false,
                data: ajaxParam({
                    externalBusinessId: window.facebookBusinessExtensionConfig.externalBusinessId,
                    catalogId: catalogId,
                    pageId: pageId,
                    accessToken: accessToken,
                    commercePartnerIntegrationId: commercePartnerIntegrationId,
                    storeId: window.facebookBusinessExtensionConfig.storeId,
                }),
                success: function onSuccess(data, _textStatus, _jqXHR) {
                    if (data.success) {
                        _this.consoleLog('Config successfully saved');
                    }
                },
                error: function () {
                    console.error('There was a problem saving config');
                }
            });
        },
        postFBEOnboardingSync: function postFBEOnboardingSync() {
            const _this = this;
            jQuery.ajax({
                type: 'post',
                url: ajaxify(window.facebookBusinessExtensionConfig.postFBEOnboardingSync),
                async: true,
                data: ajaxParam({
                    storeId: window.facebookBusinessExtensionConfig.storeId,
                }),
                success: function onSuccess(data, _textStatus, _jqXHR) {
                    if (data.success) {
                        _this.consoleLog('Post FBE Onboarding sync completed');
                    }
                },
                error: function () {
                    console.error('There was a problem with Post FBE onboarding sync');
                }
            });
        },
        deleteFBAssets: function deleteFBAssets() {
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
                    _this.cleanConfigCache();
                    _this.consoleLog(msg);
                    _this.setState({installed: 'false'});
                },
                error: function () {
                    console.error('There was a problem deleting the connection, Please try again.');
                }
            });
        },
        componentDidMount: function componentDidMount() {
            this.bindMessageEvents();
        },
        consoleLog: function consoleLog(message) {
            if (window.facebookBusinessExtensionConfig.debug) {
                console.log(message);
            }
        },
        queryParams: function queryParams() {
            return 'app_id=' + window.facebookBusinessExtensionConfig.appId +
                '&timezone=' + window.facebookBusinessExtensionConfig.timeZone +
                '&external_business_id=' + window.facebookBusinessExtensionConfig.externalBusinessId +
                '&installed=' + this.state.installed +
                '&business_vertical=' + window.facebookBusinessExtensionConfig.businessVertical +
                '&channel=' + window.facebookBusinessExtensionConfig.channel +
                '&currency=' + window.facebookBusinessExtensionConfig.currency +
                '&business_name=' + window.facebookBusinessExtensionConfig.businessName +
                '&external_client_metadata=' + getAndEncodeExternalClientMetadata();

        },
        render: function render() {
            const _this = this;
            const isNewSplashPage = window.facebookBusinessExtensionConfig.isCommerceExtensionEnabled;
            try {
                return React.createElement(
                    'iframe',
                    {
                        id: 'fbe-iframe',
                        src: window.facebookBusinessExtensionConfig.fbeLoginUrl + _this.queryParams(),
                        style: {
                            border: 'none',
                            width: isNewSplashPage ? '100%' : '1100px',
                            height: isNewSplashPage ? undefined : '700px',
                            minHeight: isNewSplashPage ? '700px' : undefined,
                        },
                        scrolling: window.facebookBusinessExtensionConfig.isCommerceExtensionEnabled
                            ? 'no'
                            : undefined,
                    }
                );
            } catch (err) {
                console.error(err);
            }
        }
    });

    // Render
    ReactDOM.render(
        React.createElement(FBEFlowContainer, null),
        document.getElementById('fbe-iframe-container')
    );
});