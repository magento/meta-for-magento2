/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */
define([
    'Meta_BusinessExtension/js/fbe_installs'
], function (fbeInstalls) {
    'use strict';
    return function (config) {
        window.facebookBusinessExtensionConfig = config.facebookBusinessExtensionConfig;
        let url = window.location.href.split('/');
        if (url.includes('facebook_business_extension')) {
           fbeInstalls.startFBEInstallsProcess();
        }
    };
});
