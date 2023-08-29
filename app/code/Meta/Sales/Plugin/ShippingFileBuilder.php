<?php

declare(strict_types=1);


namespace Meta\Sales\Plugin;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;

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
class ShippingFileBuilder
{
    /**
     * @var Filesystem
     */
    protected Filesystem $fileSystem;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(
        Filesystem $filesystem
    ) {
        $this->fileSystem = $filesystem;
    }

    /**
     * Creates file with shipping profiles that we can send to Meta
     *
     * @param array $shippingProfiles
     * @return string
     * @throws FileSystemException
     */
    public function createFile(array $shippingProfiles): string
    {
        $file = 'export/shipping_profiles.csv';
        $directory = $this->fileSystem->getDirectoryWrite(DirectoryList::APP);
        $directory->create('export');

        $stream = $directory->openFile($file, 'w+');
        $stream->lock();
        $stream->writeCsv($this->getHeaderFields());
        foreach ($shippingProfiles as $profile) {
            $stream->writeCsv($profile);
        }
        $stream->unlock();
        return $directory->getAbsolutePath($file);
    }

    /**
     * Returns a list of header fields for our CSV file
     *
     * @return array
     */
    public function getHeaderFields(): array
    {
        return [
            ShippingData::ATTR_ENABLED,
            ShippingData::ATTR_TITLE,
            ShippingData::ATTR_METHOD_NAME,
            ShippingData::ATTR_SHIPPING_METHODS,
            ShippingData::ATTR_HANDLING_FEE,
            ShippingData::ATTR_HANDLING_FEE_TYPE,
            ShippingData::ATTR_SHIPPING_FEE_TYPE,
            ShippingData::ATTR_FREE_SHIPPING_MIN_ORDER_AMOUNT
        ];
    }
}
