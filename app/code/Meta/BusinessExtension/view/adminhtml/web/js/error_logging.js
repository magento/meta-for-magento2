/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

'use strict';

define(['jquery'], function (jQuery) {

    let _isInitialized = false;
    let _storeID = null;
    let _errorRoute = null;

    const init = (errorRoute, storeID, errorEventsBeforeInit) => {
        if (_isInitialized) {
            throw new Error('error_logging.init() called multiple times');
        }

        _errorRoute = errorRoute;
        _storeID = storeID;
        _isInitialized = true;

        const ajaxParam = function (params) {
            if (window.FORM_KEY) {
                params.form_key = window.FORM_KEY;
            }
            return params;
        };

        const onError = (event) => {
            const stackTrace = event.error && event.error.stack;
            const errorLog = {
                column: event.colno,
                filename: event.filename,
                line: event.lineno,
                message: event.message,
                stackTrace: stackTrace,
                storeID: _storeID,
            };

            const ajaxURL = new URL(_errorRoute, document.baseURI);
            ajaxURL.searchParams.set('isAjax', 'true');

            jQuery.ajax({
                type: 'post',
                data: ajaxParam(errorLog),
                url: ajaxURL,
            });
        };

        window.addEventListener('error', onError);
        errorEventsBeforeInit.forEach(event => onError(event));
    };

    return {init};
});