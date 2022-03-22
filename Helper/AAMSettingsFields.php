<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Helper;

/**
 * Class that contains the keys used to identify each field in AAMSettings
 */
abstract class AAMSettingsFields
{
    const EMAIL = 'em';
    const FIRST_NAME = 'fn';
    const LAST_NAME = 'ln';
    const GENDER = 'ge';
    const PHONE = 'ph';
    const CITY = 'ct';
    const STATE = 'st';
    const ZIP_CODE = 'zp';
    const DATE_OF_BIRTH = 'db';
    const COUNTRY = 'country';
    const EXTERNAL_ID = 'external_id';

    /**
     * @return array
     */
    public static function getAllFields()
    {
        return [
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
}
