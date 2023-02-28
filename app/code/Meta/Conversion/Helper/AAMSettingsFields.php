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

/**
 * Class that contains the keys used to identify each field in AAMSettings
 */
abstract class AAMSettingsFields
{
    public const EMAIL = 'em';
    public const FIRST_NAME = 'fn';
    public const LAST_NAME = 'ln';
    public const GENDER = 'ge';
    public const PHONE = 'ph';
    public const CITY = 'ct';
    public const STATE = 'st';
    public const ZIP_CODE = 'zp';
    public const DATE_OF_BIRTH = 'db';
    public const COUNTRY = 'country';
    public const EXTERNAL_ID = 'external_id';
    public const ALL_FIELDS = [
        self::EMAIL,
        self::FIRST_NAME,
        self::LAST_NAME,
        self::GENDER,
        self::PHONE,
        self::CITY,
        self::STATE,
        self::ZIP_CODE,
        self::DATE_OF_BIRTH,
        self::COUNTRY,
        self::EXTERNAL_ID,
    ];
}
