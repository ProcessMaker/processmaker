<?php

namespace ProcessMaker\Helpers;

class SensitiveDataHelper
{
    public const SENSITIVE_KEYS = [
        'password',
        'idp.client_secret',
        'abe_imap_password',
        'EMAIL_CONNECTOR_MAIL_PASSWORD',
        'services.ldap.authentication.password'
    ];

    public const MASK = '*';

    public static function parseArray(array|object $data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $data[$key] = static::parseArray($value);
                } elseif (is_string($value) && static::isSensitiveKey($key)) {
                    $data[$key] = static::parseString($value);
                } else {
                    $data[$key] = $value;
                }
            }
        } else {
            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $data->$key = static::parseArray($value);
                } elseif (is_string($value) && static::isSensitiveKey($key)) {
                    $data->$key = static::parseString($value);
                } else {
                    $data->$key = $value;
                }
            }
        }

        return $data;
    }

    public static function isSensitiveKey($key)
    {
        $key = strtolower($key);
        $key = str_replace('+ ', '', $key);
        $key = str_replace('- ', '', $key);

        return in_array($key, static::SENSITIVE_KEYS);
    }

    public static function parseString($str)
    {
        $len = strlen($str);

        return $len > 0 ? str_repeat(static::MASK, $len) : $str;
    }
}
