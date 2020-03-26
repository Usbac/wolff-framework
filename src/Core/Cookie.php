<?php

namespace Wolff\Core;

final class Cookie
{

    const TIME = [
        'FOREVER' => 157680000, // Five years
        'MONTH'   => 2629743,
        'DAY'     => 86400,
        'HOUR'    => 3600
    ];


    /**
     * Returns the cookies or the specified cookie
     *
     * @param  string|null  $key  the key
     *
     * @return mixed the cookies or the specified cookie
     */
    public static function get(string $key = null)
    {
        if (!isset($key)) {
            return $_COOKIE;
        }

        return $_COOKIE[$key] ?? null;
    }


    /**
     * Returns true if the cookie exists, false otherwise
     *
     * @param  string  $key  the variable key
     *
     * @return bool true if the cookie exists, false otherwise
     */
    public static function has(string $key)
    {
        return array_key_exists($key, $_COOKIE);
    }


    /**
     * Sets a cookie
     *
     * @param  string  $key  the cookie key
     * @param  string  $value  the cookie value
     * @param  mixed  $time  the cookie time
     * @param  string  $path  the path where the cookie will work
     * @param  string  $domain the cookie domain
     * @param  bool  $secure  only available through https or not
     * @param  bool  $http_only  only available through http protocol or not,
     * this will hide the cookie from scripting languages like JS
     */
    public static function set(
        string $key,
        string $value,
        $time,
        string $path = '/',
        string $domain = '',
        bool $secure = true,
        bool $http_only = true
    ) {
        if (array_key_exists($time, self::TIME)) {
            $time = self::TIME[strtoupper($time)];
        }

        setCookie($key, $value, time() + $time, $path, $domain, $secure, $http_only);
    }


    /**
     * Removes a cookie
     *
     * @param  string  $key  the cookie key
     */
    public static function unset(string $key)
    {
        if (!isset($key)) {
            $_COOKIE = [];
            return;
        }

        unset($_COOKIE[$key]);
        setCookie($key, '', time() - self::TIME['HOUR']);
    }
}
