<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Meta\BusinessExtension\Helper;

use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Http\Client;

/**
 * Helper class to handle api request.
 */
class HttpClient
{
    /**
     * @var FBEHelper
     */
    private $fbeHelper;

    /**
     * Constructor
     * @param FBEHelper $helper
     */
    public function __construct(
        FBEHelper $helper
    ) {
        $this->fbeHelper = $helper;
    }

    /**
     * Make delete http call
     *
     * The curl does not support delete api call, so have to use this low level lib
     * https://devdocs.magento.com/guides/v2.3/get-started/gs-web-api-request.html
     *
     * @param string $uri
     * @return string|null
     */
    public function makeDeleteHttpCall(string $uri)
    {
        $httpHeaders = new Headers();
        $httpHeaders->addHeaders([
            'Accept' => 'application/json',
        ]);
        $request = new Request();
        $request->setHeaders($httpHeaders);
        $request->setUri($uri);
        $request->setMethod(Request::METHOD_DELETE);
        $client = new Client();
        $res = $client->send($request);
        $response = Response::fromString($res);
        $this->fbeHelper->log("response:", $response);
        return $response->getBody();
    }
}
