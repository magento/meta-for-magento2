/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */
'use strict';

var React = require('./react');
var ReactDOM = require('./react-dom');
var IEOverlay = require('./IEOverlay');
var FBModal = require('./Modal');
var FBUtils = require('./utils');

const accessTokenScope = ['manage_business_extension', 'business_management', 'ads_management', 'pages_read_engagement', 'catalog_management'];

var jQuery = (function (jQuery) {
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

var ajaxify = function (url) {
  return url + '?isAjax=true&storeId=' + window.facebookBusinessExtensionConfig.storeId;
};

var getAndEncodeExternalClientMetadata = function () {
    const metaData = {
        customer_token: window.facebookBusinessExtensionConfig.customApiKey
    };
    return encodeURIComponent(JSON.stringify(metaData));
}

var ajaxParam = function (params) {
  if (window.FORM_KEY) {
    params.form_key = window.FORM_KEY;
  }
  return params;
};

jQuery(document).ready(function() {
  var FBEFlowContainer = React.createClass({

    getDefaultProps: function() {
        console.log("init props installed "+window.facebookBusinessExtensionConfig.installed);
        return {
            installed: window.facebookBusinessExtensionConfig.installed
        };
    },
    getInitialState: function() {
        console.log("change state");
        return {installed: this.props.installed};
    },

    bindMessageEvents: function bindMessageEvents() {
      var _this = this;
      if (FBUtils.isIE() && window.MessageChannel) {
        // do nothing, wait for our messaging utils to be ready
      } else {
        window.addEventListener('message', function (event) {
          var origin = event.origin || event.originalEvent.origin;
          if (FBUtils.urlFromSameDomain(origin, window.facebookBusinessExtensionConfig.popupOrigin)) {
            // Make ajax calls to store data from fblogin and fb installs
            _this.consoleLog("Message from fblogin ");

            const message = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
            if (!message) {
              return;
            }

            _this.handleMessage(message);
          }
        }, false);
      }
    },
    handleMessage: function handleMessage(message) {
      const _this = this;
      _this.consoleLog("Response from fb login:");
      _this.consoleLog(message);

      // "FBE Iframe" uses the 'action' field in its messages.
      // "Commerce Extension" uses the 'type' field in its messages.
      const action = message.action;
      const messageEvent = message.event;

      if (action === 'delete' || messageEvent === 'CommerceExtension::UNINSTALL') {
        // Delete asset ids stored in db instance.
        _this.consoleLog("Successfully uninstalled FBE");
        _this.deleteFBAssets();
      }

      if (action === 'create' || messageEvent === 'CommerceExtension::INSTALL') {
        const success = message.success;
        if (success) {
          const businessManagerId = message.business_manager_id;
          const accessToken = message.access_token;
          const pixelId = message.pixel_id;
          const profiles = message.profiles;
          const catalogId = message.catalog_id;
          const commercePartnerIntegrationId = message.commerce_partner_integration_id;
          const pageId = message.page_id;
          const installedFeatures = message.installed_features;

          _this.savePixelId(pixelId);
          _this.exchangeAccessToken(accessToken, businessManagerId);
          _this.saveProfilesData(profiles);
          _this.saveAAMSettings(pixelId);
          _this.saveConfig(accessToken, catalogId, pageId, commercePartnerIntegrationId);
          _this.saveInstalledFeatures(installedFeatures);
          _this.cleanConfigCache();
          _this.postFBEOnboardingSync();

          if (window.facebookBusinessExtensionConfig.isCommerceEmbeddedExtensionEnabled) {
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
      var _this = this;
      if (!pixelId) {
        console.error('Meta Business Extension Error: got no pixel_id');
        return;
      }
      jQuery.ajax({
        type: 'post',
        url: ajaxify(window.facebookBusinessExtensionConfig.setPixelId),
        async : false,
        data: ajaxParam({
          pixelId: pixelId,
          storeId: window.facebookBusinessExtensionConfig.storeId,
        }),
        success: function onSuccess(data, _textStatus, _jqXHR) {
          var response = data;
          let msg = '';
          if (response.success) {
            _this.setState({pixelId: response.pixelId});
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
      var _this = this;
      if (!accessToken) {
        console.error('Meta Business Extension Error: got no access token');
        return;
      }
      jQuery.ajax({
        type: 'post',
        url: ajaxify(window.facebookBusinessExtensionConfig.setAccessToken),
        async : false,
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
    exchangeAccessToken: function exchangeAccessToken(access_token, business_manager_id) {
      const _this = this;
      const fbeAccessTokenUrl = window.facebookBusinessExtensionConfig.fbeAccessTokenUrl;
      if (!fbeAccessTokenUrl) {
        console.error('Could not exchange access token. Token url not found.');
        return;
      }
      let requestData = {
          'access_token': access_token,
          'app_id': window.facebookBusinessExtensionConfig.appId,
          'fbe_external_business_id': window.facebookBusinessExtensionConfig.externalBusinessId,
          'scope': accessTokenScope.join()
      };
      jQuery.ajax({
        type: 'post',
        url: fbeAccessTokenUrl.replace("business_manager_id", business_manager_id),
        async : false,
        data: requestData,
        success: function onSuccess(data, _textStatus, _jqXHR) {
            _this.saveAccessToken(data.access_token);
        },
        error: function () {
          console.error('There was an error getting access_token');
        }
      });
    },
    saveProfilesData: function saveProfilesData(profiles) {
      var _this = this;
      if (!profiles) {
        console.error('Meta Business Extension Error: got no profiles data');
        return;
      }
      jQuery.ajax({
        type: 'post',
        url: ajaxify(window.facebookBusinessExtensionConfig.setProfilesData),
        async : false,
        data: ajaxParam({
          profiles: JSON.stringify(profiles),
        }),
        success: function onSuccess(data, _textStatus, _jqXHR) {
          _this.consoleLog('set profiles data ' +  data.profiles);
        },
        error: function () {
          console.error('There was problem saving profiles data', profiles);
        }
      });
    },
    saveAAMSettings: function saveAAMSettings(pixelId){
      var _this = this;
        jQuery.ajax({
        'type': 'post',
        url: ajaxify(window.facebookBusinessExtensionConfig.setAAMSettings),
        async : false,
        data: ajaxParam({
          pixelId: pixelId,
        }),
        success: function onSuccess(data, _textStatus, _jqXHR) {
          if(data.success){
            _this.consoleLog('AAM settings successfully saved '+data.settings);
          }
          else{
            _this.consoleLog('AAM settings could not be read for the given pixel');
          }
        },
        error: function (){
          _this.consoleLog('There was an error retrieving AAM settings');
        }
      });
    },
    saveInstalledFeatures: function saveInstalledFeatures(installedFeatures) {
      var _this = this;
      if (!installedFeatures) {
        console.error('Meta Business Extension Error: got no installed_features data');
        return;
      }
      jQuery.ajax({
        type: 'post',
        url: ajaxify(window.facebookBusinessExtensionConfig.setInstalledFeatures),
        async : false,
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
    cleanConfigCache : function cleanConfigCache() {
      var _this = this;
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
        error: function() {
          console.error('There was a problem cleaning config cache');
        }
      });
    },
    saveConfig: function saveConfig(accessToken, catalogId, pageId, commercePartnerIntegrationId) {
      var _this = this;
      jQuery.ajax({
        type: 'post',
        url: ajaxify(window.facebookBusinessExtensionConfig.saveConfig),
        async : false,
        data: ajaxParam({
          externalBusinessId: window.facebookBusinessExtensionConfig.externalBusinessId,
          catalogId: catalogId,
          pageId: pageId,
          accessToken: accessToken,
          commercePartnerIntegrationId: commercePartnerIntegrationId,
          storeId: window.facebookBusinessExtensionConfig.storeId,
        }),
        success: function onSuccess(data, _textStatus, _jqXHR) {
          if(data.success) {
            _this.consoleLog('Config successfully saved');
          }
        },
        error: function() {
          console.error('There was a problem saving config');
        }
      });
    },
    postFBEOnboardingSync: function postFBEOnboardingSync() {
      var _this = this;
      jQuery.ajax({
        type: 'post',
        url: ajaxify(window.facebookBusinessExtensionConfig.postFBEOnboardingSync),
        async : true,
        data: ajaxParam({
          storeId: window.facebookBusinessExtensionConfig.storeId,
        }),
        success: function onSuccess(data, _textStatus, _jqXHR) {
          if(data.success) {
              _this.consoleLog('Post FBE Onboarding sync completed');
          }
        },
        error: function() {
          console.error('There was a problem with Post FBE onboarding sync');
        }
      });
    },
    deleteFBAssets: function deleteFBAssets() {
      var _this = this;
        jQuery.ajax({
        type: 'delete',
        url: ajaxify(window.facebookBusinessExtensionConfig.deleteConfigKeys),
        data: ajaxParam({
            storeId: window.facebookBusinessExtensionConfig.storeId,
        }),
        success: function onSuccess(data, _textStatus, _jqXHR) {
          let msg = '';
          if(data.success) {
            msg = data.message;
          }else {
            msg = data.error_message;
          }
          _this.cleanConfigCache();
          _this.consoleLog(msg);
          _this.setState({installed: 'false'});
        },
        error: function() {
          console.error('There was a problem deleting the connection, Please try again.');
        }
      });
    },
    componentDidMount: function componentDidMount() {
      this.bindMessageEvents();
    },
    consoleLog: function consoleLog(message) {
      if(window.facebookBusinessExtensionConfig.debug) {
        console.log(message);
      }
    },
    queryParams: function queryParams() {
      return 'app_id='+window.facebookBusinessExtensionConfig.appId +
             '&timezone='+window.facebookBusinessExtensionConfig.timeZone+
             '&external_business_id='+window.facebookBusinessExtensionConfig.externalBusinessId+
             '&installed='+this.state.installed+
             '&system_user_name='+window.facebookBusinessExtensionConfig.systemUserName+
             '&business_vertical='+window.facebookBusinessExtensionConfig.businessVertical+
             '&channel='+window.facebookBusinessExtensionConfig.channel+
             '&currency='+ window.facebookBusinessExtensionConfig.currency +
             '&business_name='+ window.facebookBusinessExtensionConfig.businessName +
             '&external_client_metadata=' + getAndEncodeExternalClientMetadata();

    },
    render: function render() {
      const _this = this;
      const isNewSplashPage = window.facebookBusinessExtensionConfig.isCommerceEmbeddedExtensionSplashEnabled;
      try {
        _this.consoleLog("query params --"+_this.queryParams());
        return React.createElement(
          'iframe',
          {
            id: 'fbe-iframe',
            src: window.facebookBusinessExtensionConfig.fbeLoginUrl + _this.queryParams(),
            style: {
              border: 'none',
              width: '1100px',
              height: isNewSplashPage ? undefined : '700px',
              minHeight: isNewSplashPage ? '700px' : undefined,
            },
            scrolling: window.facebookBusinessExtensionConfig.isCommerceEmbeddedExtensionSplashEnabled
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
// Code to display the above container.
var displayFBModal = function displayFBModal() {
  if (FBUtils.isIE()) {
    IEOverlay().render();
  }
  var QueryString = function () {
    // This function is anonymous, is executed immediately and
    // the return value is assigned to QueryString!
    var query_string = {};
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i = 0; i < vars.length; i++) {
      var pair = vars[i].split("=");
      // If first entry with this name
      if (typeof query_string[pair[0]] === "undefined") {
        query_string[pair[0]] = decodeURIComponent(pair[1]);
        // If second entry with this name
      } else if (typeof query_string[pair[0]] === "string") {
        var arr = [query_string[pair[0]], decodeURIComponent(pair[1])];
        query_string[pair[0]] = arr;
        // If third or later entry with this name
      } else {
        query_string[pair[0]].push(decodeURIComponent(pair[1]));
      }
    }
    return query_string;
  }();
  if (QueryString.p) {
    window.facebookBusinessExtensionConfig.popupOrigin = QueryString.p;
  }
};

(function main() {
  // Logic for when to display the container.
  if (document.readyState === 'interactive') {
    // in case the document is already rendered
    displayFBModal();
  } else if (document.addEventListener) {
    // modern browsers
    document.addEventListener('DOMContentLoaded', displayFBModal);
  } else {
    document.attachEvent('onreadystatechange', function () {
      // IE <= 8
      if (document.readyState === 'complete') {
        displayFBModal();
      }
    });
  }
})();
