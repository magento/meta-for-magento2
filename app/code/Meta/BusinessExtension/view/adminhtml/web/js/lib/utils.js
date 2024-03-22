/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

'use strict';

var FBUtils = (function(){
    return {
        parseURL: function parseURL(url) {
            const parser = document.createElement('a');
            parser.href = url;
            return parser;
        },

        urlFromSameDomain : function urlFromSameDomain(url1, url2) {
            var u1 = FBUtils.parseURL(url1);
            var u2 = FBUtils.parseURL(url2);
            var u1host = u1.host.replace('web.', 'www.');
            var u2host = u2.host.replace('web.', 'www.');
            return u1.protocol === u2.protocol && u1host === u2host;
        }
    }
}());

module.exports = FBUtils;
