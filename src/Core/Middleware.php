<?php

namespace Wolff\Core;

use Wolff\Utils\Str;
use Wolff\Exception\InvalidArgumentException;

/**
 * @method static $this before(string $url, \Closure $func)
 * @method static $this after(string $url, \Closure $func)
 */
final class Middleware
{

    /**
     * List of middlewares of type before
     *
     * @var array
     */
    private static $before = [];

    /**
     * List of middlewares of type after
     *
     * @var array
     */
    private static $after = [];

    /**
     * Continue or not to the next middleware
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
     * @param  Http\Response|null  $res  the response object
     *
     * @return string the middleware responses joined
     */
    private static function load(array $middlewares, string $url, Http\Request &$req = null, Http\Response &$res = null): string
    {
        if (empty($middlewares)) {
            return '';
        }

        $url = explode('/', rtrim($url, '/'));
        $url_len = count($url) - 1;
        $data = [];
        $args = [
            $req,
            function () { self::$next = true; },
            $res,
        ];

        foreach ($middlewares as $middleware) {
            if (!Helper::matchesRoute($middleware['url'], $url, $url_len)) {
                continue;
            }

            self::$next = false;

            array_push($data, call_user_func_array($middleware['func'], $args));

            if (!self::$next) {
                break;
            }
        }

        return implode('', $data);
    }


    /**
     * Loads the middlewares files of type before that matches the current route
     *
     * @param  string  $url  the url to match the middlewares
     * @param  Http\Request|null  $req  the request object
     * @param  Http\Response|null  $req  the response object
     *
     * @return string the middleware responses
     */
    public static function loadBefore(string $url, Http\Request $req = null, Http\Response $res = null): string
    {
        return self::load(self::$before, $url, $req, $res);
    }


    /**
     * Loads the middlewares files of type after that matches the current route
     *
     * @param  string  $url  the url to match the middlewares
     * @param  Http\Request|null  $req  the request object
     * @param  Http\Response|null  $req  the response object
     *
     * @return string the middleware responses
     */
    public static function loadAfter(string $url, Http\Request $req = null, Http\Response $res = null): string
    {
        return self::load(self::$after, $url, $req, $res);
    }


    /**
     * Proxy to call the middlewares of type before and after
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
        } elseif (!($args[1] instanceof \Closure)) {
            throw new InvalidArgumentException('func', 'an instance of \Closure');
        }

        array_push(self::${$type}, [
            'url'  => Str::sanitizeURL($args[0]),
            'func' => $args[1],
        ]);
    }
}
