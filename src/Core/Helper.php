<?php

namespace Wolff\Core;

class Helper
{

    /**
     * Returns true if the given array is
     * associative (numbers as keys), false otherwise.
     *
     * @param  array  $arr  the array
     *
     * @return bool true if the given array is associative,
     * false otherwise
     */
    public static function isAssoc(array $arr)
    {
        return (array_keys($arr) !== range(0, count($arr) - 1));
    }


    /**
     * Returns the current client IP
     * @return string the current client IP
     */
    public static function getClientIP()
    {
        $http_client_ip = filter_var($_SERVER['HTTP_CLIENT_IP'] ?? '', FILTER_VALIDATE_IP);
        $http_forwarded = filter_var($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '', FILTER_VALIDATE_IP);

        if (!empty($http_client_ip)) {
            return $http_client_ip;
        }

        if (!empty($http_forwarded)) {
            return $http_forwarded;
        }

        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
}
