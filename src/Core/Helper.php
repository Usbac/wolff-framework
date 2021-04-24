<?php

namespace Wolff\Core;

final class Helper
{

    /**
     * The root directory of the current project
     *
     * @var string
     */
    private static $base_path = null;


    /**
     * Returns the key value of the given array, or null if it doesn't exists.
     * The key param can use the dot notation, like 'user.name'.
     *
     * @param  array  $arr  the array
     * @param  string|null  $key  the array key to obtain
     *
     * @return mixed the value of the specified key in the array
     */
    public static function val(array $arr, string $key = null)
    {
        if (is_null($key)) {
            return $arr;
        }

        foreach (explode('.', $key) as $key) {
            if (!is_array($arr) || !array_key_exists($key, $arr)) {
                return null;
            }

            $arr = &$arr[$key];
        }

        return $arr;
    }


    /**
     * Returns the given string without single or double quotes surrounding it
     *
     * @param  string  $str  the string
     *
     * @return string the string without single or double quotes surrounding it
     */
    public static function removeQuotes(string $str): string
    {
        $len = strlen($str) - 1;
        if (($str[0] === '\'' && $str[$len] === '\'') ||
            ($str[0] === '"' && $str[$len] === '"')) {
            $str = substr($str, 1, $len - 1);
        }

        return $str;
    }


    /**
     * Returns the given relative path as absolute
     *
     * @param  string  $path  the path (relative to the project root folder)
     *
     * @return string the absolute path
     */
    public static function getRoot(string $path = ''): string
    {
        if (!isset(self::$base_path)) {
            self::$base_path = str_replace('\\', '/', dirname(getcwd()));
        }

        return self::$base_path . '/' . $path;
    }


    /**
     * Returns true if the given array is associative, false otherwise
     *
     * @param  array  $arr  the array
     *
     * @return bool true if the given array is associative, false otherwise
     */
    public static function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }


    /**
     * Removes an element from the given array based on its value
     *
     * @param  array  $arr  the array
     * @param  mixed  $needle  the value to remove
     *
     * @return bool true if the element has been removed, false otherwise
     */
    public static function arrayRemove(array &$arr, $needle): bool
    {
        foreach ($arr as $key => $val) {
            if ($val == $needle) {
                unset($arr[$key]);
                return true;
            }
        }

        return false;
    }


    /**
     * Returns the current client IP
     *
     * @return string the current client IP
     */
    public static function getClientIP(): string
    {
        $client_ip = filter_var($_SERVER['HTTP_CLIENT_IP'] ?? '', FILTER_VALIDATE_IP);
        $forwarded = filter_var($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '', FILTER_VALIDATE_IP);

        if (!empty($client_ip)) {
            return $client_ip;
        } elseif (!empty($forwarded)) {
            return $forwarded;
        }

        return $_SERVER['REMOTE_ADDR'] ?? '';
    }


    /**
     * Returns true if the given url array matches the route array,
     * false otherwise
     *
     * @param  string  $route  the route that must be matched
     * @param  array  $url  the url to test
     * @param  int  $url_len  the url length
     *
     * @return bool true if the given url array matches the route array,
     * false otherwise
     */
    public static function matchesRoute(string $route, array $url, int $url_len): bool
    {
        $route = explode('/', $route);
        $route_len = count($route) - 1;

        for ($i = 0; $i <= $route_len && $i <= $url_len; $i++) {
            if ($route[$i] !== $url[$i] && $route[$i] !== '*') {
                break;
            }

            if ($route[$i] === '*' ||
                ($i === $url_len && $i === $route_len)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Returns the current page relative to the project url
     *
     * @return string the current page relative to the project url
     */
    public static function getCurrentPage(): string
    {
        $url = $_SERVER['REQUEST_URI'];
        $root = self::getRoot();

        // Remove possible project folder from url
        if (strpos($root, $_SERVER['DOCUMENT_ROOT']) === 0) {
            $url = substr($url, strlen($root) - strlen($_SERVER['DOCUMENT_ROOT']));
        }

        return $url;
    }


    /**
     * Returns true if a string ends with another string, false otherwise
     *
     * @param  string  $str  the string
     * @param  string  $needle  the substring
     *
     * @return bool true if a string ends with another string, false otherwise
     */
    public static function endsWith(string $str, string $needle): bool
    {
        return mb_substr($str, -mb_strlen($needle)) === $needle;
    }
}
