<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Logger;

use Magento\Framework\ObjectManagerInterface;

class Logger extends \Monolog\Logger
{
    /**
     * Logger constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $handler = $objectManager->create(Handler::class);
        parent::__construct('FBE', [$handler]);
    }
}
