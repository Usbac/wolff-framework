<?php

namespace Wolff\Core;

use Wolff\Utils\Str;
use Wolff\Exception\InvalidArgumentException;

/**
 * static @method before(string $url, \Closure $function)
 * static @method after(string $url, \Closure $function)
 */
final class Middleware
{

    /**
     * List of middlewares
     * of type before
     *
     * @var array
     */
    private static $before = [];

    /**
     * List of middlewares
     * of type after
     *
     * @var array
     */
    private static $after = [];

    /**
     * Continue or not to the
     * next middleware
     *
     * @var bool
     */
    private static $next = false;


    /**
     * Loads all the middlewares that matches the current route
     *
     * @param  array  $middlewares  the array of middlewares to load
     * @param  string  $url  the url to match the middlewares
     * @param  Http\Request|null  $req  the request object
     *
     * @return string the middleware responses joined
     */
    private static function load(array $middlewares, string $url, Http\Request &$req = null): string
    {
        if (empty($middlewares)) {
            return '';
        }

        $args = [
            $req,
            function () {
                self::$next = true;
            }
        ];

        $results = [];
        $url = explode('/', rtrim($url, '/'));
        $url_len = count($url) - 1;

        foreach ($middlewares as $middleware) {
            if (!Helper::matchesRoute($middleware['url'], $url, $url_len)) {
                continue;
            }

            self::$next = false;
            $result = call_user_func_array($middleware['function'], $args);
            array_push($results, $result);

            if (!self::$next) {
                break;
            }
        }

        return implode('', $results);
    }


    /**
     * Loads the middlewares files of type before that matches the current route
     *
     * @param  string  $url  the url to match the middlewares
     * @param  Http\Request|null  $req  the request object
     *
     * @return string the middleware responses
     */
    public static function loadBefore(string $url, Http\Request $req = null): string
    {
        return self::load(self::$before, $url, $req);
    }


    /**
     * Loads the middlewares files of type after that matches the current route
     *
     * @param  string  $url  the url to match the middlewares
     * @param  Http\Request|null  $req  the request object
     *
     * @return string the middleware responses
     */
    public static function loadAfter(string $url, Http\Request $req = null): string
    {
        return self::load(self::$after, $url, $req);
    }


    /**
     * Proxy to call the middlewares of type
     * before and after
     *
     * @throws \Wolff\Exception\InvalidArgumentException
     *
     * @param  string  $type  the type of middlewares to load
     * @param  array  $args  the arguments
     */
    public static function __callStatic(string $type, $args): void
    {
        if ($type !== 'before' && $type !== 'after') {
            return;
        }

        if (!is_string($args[0])) {
            throw new InvalidArgumentException('url', 'of type string');
        }

        if (!($args[1] instanceof \Closure)) {
            throw new InvalidArgumentException('function', 'an instance of \Closure');
        }

        array_push(self::${$type}, [
            'url'      => Str::sanitizeURL($args[0]),
            'function' => $args[1]
        ]);
    }
}
