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

namespace Meta\Conversion\Helper;

class EventIdGenerator
{
    /**
     * Generate random id
     *
     * @return string A 36 character string containing dashes.
     */
    public static function guidv4() // phpcs:ignore
    {
        $data = openssl_random_pseudo_bytes(16);

        // set version to 0100
        // phpcs:ignore
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // set bits 6-7 to 10
        // phpcs:ignore
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
