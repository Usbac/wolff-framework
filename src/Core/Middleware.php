<?php

namespace Wolff\Core;

use Wolff\Core\Helper;
use Wolff\Utils\Str;
use Wolff\Exception\InvalidArgumentException;

/**
 * @method static before(string $url, \Closure $function)
 * @method static after(string $url, \Closure $function)
 */
final class Middleware
{

    /**
     * List of middlewares
     * of type before
     *
     * @var array
     */
    private static $middlewares_before = [];

    /**
     * List of middlewares
     * of type after
     *
     * @var array
     */
    private static $middlewares_after = [];

    /**
     * Continue or not to the
     * next middleware
     *
     * @var bool
     */
    private static $next = false;


    /**
     * Loads all the middlewares files that matches the current route
     *
     * @param  string  $type  the type of middlewares to load
     * @param  string  $url  the url to match the middlewares
     * @param  Http\Request  $req  the request object
     */
    private static function load(string $type, string $url, Http\Request &$req)
    {
        if (empty(self::${'middlewares_' . $type})) {
            return;
        }

        $middlewares = self::${'middlewares_' . $type};
        self::$next = false;
        $args = [
            $req,
            function () {
                self::$next = true;
            }
        ];

        $url = explode('/', $url);
        $url_length = count($url) - 1;
        foreach ($middlewares as $middleware) {
            if (Helper::matchesRoute($middleware['url'], $url, $url_length)) {
                call_user_func_array($middleware['function'], $args);

                if (!self::$next) {
                    break;
                }
            }
        }
    }


    /**
     * Loads the middlewares files of type before that matches the current route
     *
     * @param  string  $url  the url to match the middlewares
     * @param  Http\Request  $req  the request object
     */
    public static function loadBefore(string $url, Http\Request $req)
    {
        self::load('before', $url, $req);
    }


    /**
     * Loads the middlewares files of type after that matches the current route
     *
     * @param  string  $url  the url to match the middlewares
     * @param  Http\Request  $req  the request object
     */
    public static function loadAfter(string $url, Http\Request $req)
    {
        self::load('after', $url, $req);
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
    public static function __callStatic(string $type, $args)
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

        array_push(self::${'middlewares_' . $type}, [
            'url'      => Str::sanitizeURL($args[0]),
            'function' => $args[1]
        ]);
    }
}
