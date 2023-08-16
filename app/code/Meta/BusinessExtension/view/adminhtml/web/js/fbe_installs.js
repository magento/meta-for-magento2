/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

define(['jquery'], function(jQuery) {
    'use strict';
    const ajaxify = function (url, storeId) {
      return url + '?isAjax=true&storeId=' + storeId;
    };
    const ajaxParam = function (params) {
        if (window.FORM_KEY) {
            params.form_key = window.FORM_KEY;
        }
        return params;
    };
    return {
        getStoreId: function() {
            const storeId = window.facebookBusinessExtensionConfig.storeId;
            const websiteId = window.facebookBusinessExtensionConfig.websiteId;
            if (!storeId && !websiteId ) { // User is on the 'default config'
                return window.facebookBusinessExtensionConfig.defaultStoreId;
            }
            if (websiteId) { // Skip if user is on the website scope
                return '';
            }
            return storeId;
        },
        saveFBEInstallsData: function(responseData) {
          const storeId = this.getStoreId();
            if (!storeId) {
                console.error("Could not save FBEInstalls data. No storeId.");
                return;
            }
          jQuery.ajax({
            type: 'post',
            url: ajaxify(window.facebookBusinessExtensionConfig.fbeInstallsSaveUrl, storeId),
            async : false,
            data: ajaxParam(responseData),
            success: function onSuccess(data, _textStatus, _jqXHR) {
              if (data.success) {
                console.log("Successfully saved FBEInstalls data.");
              } else {
                console.log("There was an issue with saving the FBEInstalls data. Check the admin logs for more information.");
              }
            },
            error: function () {
              console.error("There was an issue with saving the FBEInstalls data. Check the admin logs for more information.");
            }
          });
        },
        callFBEInstalls: function(data) {
          const _this = this;
          const { endpoint, externalBusinessId, accessToken } = data;
          jQuery.ajax({
            type: 'get',
            url: endpoint,
            async : false,
            data: {
              'fbe_external_business_id': externalBusinessId,
              'access_token': accessToken,
            },
            success: function onSuccess(data, _textStatus, _jqXHR) {
              _this.saveFBEInstallsData(data);
            },
            error: function () {
              console.error('There was an error with the FBEInstalls API');
            }
          });
        },
        startFBEInstallsProcess: function() {
          const storeId = this.getStoreId();
          if (!storeId) {
                return;
          }
          const _this = this;
          // Get endpoint, token and business ID to call FBEInstalls API
          jQuery.ajax({
            type: 'get',
            url: window.facebookBusinessExtensionConfig.fbeInstallsConfigUrl,
            async : true,
            data: {
                storeId: storeId 
            },
            success: function onSuccess(data, _textStatus, _jqXHR) {
                if (data.accessToken && data.externalBusinessId) {
                    _this.callFBEInstalls(data);
                }
            },
            error: function () {
              console.error('There was an error retreiving FBE installs config');
            }
          });
        },
        attachStoreEventHandler: function () {
            const _this = this;
            jQuery('#store').on('change', function() {
              if (jQuery(this).val() === 'select-store') {
                jQuery('#fbe-iframe').empty();
                return false;
              }
                window.facebookBusinessExtensionConfig.storeId = jQuery(this).val();
                window.facebookBusinessExtensionConfig.installed = jQuery(this).find(':selected').data('installed');
                window.facebookBusinessExtensionConfig.pixelId = jQuery(this).find(':selected').data('pixel-id');
                window.facebookBusinessExtensionConfig.systemUserName = jQuery(this).find(':selected').data('system-user-name') + '_system_user';
                window.facebookBusinessExtensionConfig.externalBusinessId = jQuery(this).find(':selected').data('external-business-id');
                _this.startFBEInstallsProcess();
            });
        } 
    }
});
