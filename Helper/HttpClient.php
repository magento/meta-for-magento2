<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Helper;

use Zend\Http\Headers;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Http\Client;

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
     * @param string $uri
     * the curl does not support delete api call, so have to use this low level lib
     * https://devdocs.magento.com/guides/v2.3/get-started/gs-web-api-request.html
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
