<?php

namespace Wolff\Core;

use Wolff\Core\Helper;
use Wolff\Utils\Str;
use Wolff\Exception\InvalidArgumentException;

/**
 * static @method get(string $url, $func, int $status = null)
 * static @method post(string $url, $func, int $status = null)
 * static @method put(string $url, $func, int $status = null)
 * static @method patch(string $url, $func, int $status = null)
 * static @method delete(string $url, $func, int $status = null)
 */
final class Route
{

    const STATUS_OK = 200;
    const STATUS_REDIRECT = 301;
    const GET_FORMAT = '/\{(.*)\}/';
    const OPTIONAL_GET_FORMAT = '/\{(.*)\?\}/';
    const PREFIXES = [
        'csv:'   => 'text/csv',
        'json:'  => 'application/json',
        'pdf:'   => 'application/pdf',
        'plain:' => 'text/plain',
        'xml:'   => 'application/xml',
    ];
    const HTTP_METHODS = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
    ];

    /**
     * List of static routes
     *
     * @var array
     */
    private static $static_routes = [];

    /**
     * List of dynamic routes
     *
     * @var array
     */
    private static $dynamic_routes = [];

    /**
     * List of routes for status codes
     */
    private static $codes = [];

    /**
     * List of blocked routes
     *
     * @var array
     */
    private static $blocked = [];

    /**
     * List of redirects
     *
     * @var array
     */
    private static $redirects = [];


    /**
     * Proxy method to the HTTP Methods
     *
     * @throws \Wolff\Exception\InvalidArgumentException
     *
     * @param  string  $name  the method name
     * @param  mixed  $args  the method arguments
     */
    public static function __callStatic(string $name, $args): void
    {
        $http_method = strtoupper($name);
        if (!in_array($http_method, self::HTTP_METHODS)) {
            return;
        }

        if (!isset($args[0]) || !is_string($args[0])) {
            throw new InvalidArgumentException('url', 'of type string');
        } elseif (!isset($args[1]) || (!is_array($args[1]) && !($args[1] instanceof \Closure))) {
            throw new InvalidArgumentException('function', 'of type array or an instance of \Closure');
        }

        $status = isset($args[2]) && is_numeric($args[2]) ?
            (int)$args[2] :
            null;

        self::addRoute(Str::sanitizeURL($args[0]), $http_method, $args[1], $status);
    }


    /**
     * Adds a route that renders a view
     *
     * @param  string  $url  the url
     * @param  string  $view  the view path
     * @param  array  $data  the view data
     * @param  bool  $cache  use or not the cache system
     */
    public static function view(string $url, string $view, array $data = [], bool $cache = true): void
    {
        self::addRoute($url,
            'GET',
            function () use ($view, $data, $cache) {
                View::render($view, $data, $cache);
            },
            null);
    }


    /**
     * Adds a route that will work only for a status code
     *
     * @param  int  $code  the status code
     * @param  \Closure  $func  mixed the function that must be executed
     * when getting the status code
     */
    public static function code(int $code, \Closure $func): void
    {
        self::$codes[$code] = $func;
    }


    /**
     * Executes a code route based on the given status code
     *
     * @param  int  $code  the status code
     * @param  \Wolff\Core\Http\Request  $req  the current request object
     * @param  \Wolff\Core\Http\Response  $res  the current response object
     */
    public static function execCode(int $code, Http\Request &$req, Http\Response &$res): void
    {
        if (isset(self::$codes[$code])) {
            call_user_func_array(self::$codes[$code], [ $req, $res ]);
        }
    }


    /**
     * Returns the value of a route
     *
     * @param  string  $url  the url
     *
     * @return mixed the value associated to the route
     */
    public static function getFunction(string $url)
    {
        //Static routes
        foreach (self::$static_routes as $key => $val) {
            if (self::isValidRoute($val) && $key === $url) {
                return self::processRoute($val);
            }
        }

        //Dynamic routes
        $current = array_filter(explode('/', $url));
        $len = count($current) - 1;

        foreach (self::$dynamic_routes as $key => $val) {
            if (self::isValidRoute($val)) {
                $route = array_filter(explode('/', $key));

                if (self::matchesRoute($current, $len, $route, count($route) - 1)) {
                    self::mapParameters($current, $route);

                    return self::processRoute($val);
                }
            }
        }

        return null;
    }


    /**
     * Returns true if the current route matches the given one, false otherwise
     *
     * @param  array  $current  the current route array
     * @param  int  $current_len  the size of the current route array
     * @param  array  $route  the route array to test
     * @param  int  $route_len  the size of the route array to test
     *
     * @return bool true if the current route matches the given one, false otherwise
     */
    private static function matchesRoute(array $current, int $current_len, array $route, int $route_len)
    {
        if (empty($current) && empty($route)) {
            return true;
        }

        for ($i = 0; $i <= $route_len && $i <= $current_len; $i++) {
            if ($current[$i] !== $route[$i] && !self::isGet($route[$i])) {
                break;
            }

            if (($i === $route_len || ($i + 1 === $route_len && self::isOptionalGet($route[$i + 1]))) &&
                $i === $current_len) {
                return true;
            }
        }

        return false;
    }


    /**
     * Sets the route response code and content-type and returns its function
     *
     * @param  array  $route  the route
     *
     * @return mixed the route function
     */
    private static function processRoute(array $route)
    {
        header('Content-Type: ' . $route['content_type']);
        if (isset($route['status'])) {
            http_response_code($route['status']);
        }

        if (!is_array($route['action'])) {
            return $route['action'];
        }

        if (isset($route['action'][0], $route['action'][1])) {
            return function (...$args) use ($route) {
                (new $route['action'][0])->{$route['action'][1]}(...$args);
            };
        }

        return null;
    }


    /**
     * Maps the current route GET parameters
     *
     * @param  array  $current  the current route array
     * @param  array  $route  the route array which matches the current route
     */
    private static function mapParameters(array $current, array $route)
    {
        $current_len = count($current) - 1;
        $route_len = count($route) - 1;

        for ($i = 0; $i <= $route_len && $i <= $current_len; $i++) {
            if (self::isOptionalGet($route[$i])) {
                self::setOptionalGetVar($route[$i], $current[$i]);
            } elseif (self::isGet($route[$i])) {
                self::setGet($route[$i], $current[$i]);
            }

            //Finish if last GET variable from url is optional
            if ($i + 1 === $route_len && $i === $current_len &&
                self::isOptionalGet($route[$i + 1])) {
                self::setOptionalGetVar($route[$i], $current[$i]);
                return;
            }
        }
    }


    /**
     * Returns true if the route is set and its method matches the current method
     *
     * @param  array|null  $route  the route route
     *
     * @return bool true if the route exists and its method matches the current method
     */
    private static function isValidRoute(?array $route): bool
    {
        return isset($route) && ($route['method'] === '' ||
            $route['method'] === $_SERVER['REQUEST_METHOD']);
    }


    /**
     * Adds a route that works for any method
     *
     * @param  string  $url  the url
     * @param  mixed  $func  mixed the function to call
     * @param  int  $status  the HTTP response code
     */
    public static function any(string $url, $func, int $status = self::STATUS_OK): void
    {
        self::addRoute(Str::sanitizeURL($url), '', $func, $status);
    }


    /**
     * Redirects the first url to the second url
     *
     * @param  string  $from  the origin url
     * @param  string  $to  the destiny url
     * @param  int  $code  the HTTP response code
     */
    public static function redirect(string $from, string $to, int $code = self::STATUS_REDIRECT): void
    {
        self::$redirects[] = [
            'from' => $from,
            'to'   => Str::sanitizeURL($to),
            'code' => $code,
        ];
    }


    /**
     * Adds a route to the list
     *
     * @param  mixed  $url  the url
     * @param  string  $method  the url HTTP method
     * @param  mixed  $function  the url function
     * @param  int|null  $status  the HTTP response code
     */
    private static function addRoute($url, string $method, $function, ?int $status): void
    {
        $content_type = 'text/html';

        //Remove content-type prefix from route
        foreach (self::PREFIXES as $key => $val) {
            if (strpos($url, $key) === 0) {
                $url = substr($url, strlen($key));
                $content_type = $val;
            }
        }

        $url = trim($url, '/');
        $route = [
            'action'       => $function,
            'method'       => $method,
            'status'       => $status,
            'content_type' => $content_type,
        ];

        preg_match(self::GET_FORMAT, $url, $matches);
        empty($matches) ?
            self::$static_routes[$url] = $route :
            self::$dynamic_routes[$url] = $route;
    }


    /**
     * Blocks an url
     *
     * @param  string  $url  the url
     */
    public static function block(string $url): void
    {
        array_push(self::$blocked, Str::sanitizeURL($url));
    }


    /**
     * Check if an url is blocked
     *
     * @param  string  $url  the url
     *
     * @return bool true if the url is blocked, false otherwise
     */
    public static function isBlocked(string $url): bool
    {
        if (empty(self::$blocked)) {
            return false;
        }

        $url = explode('/', $url);
        $url_len = count($url) - 1;

        foreach (self::$blocked as $blocked) {
            if (Helper::matchesRoute($blocked, $url, $url_len)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Check if a route exists
     *
     * @param  string  $url  the url
     *
     * @return bool true if the route exists, false otherwise
     */
    public static function exists(string $url): bool
    {
        foreach (self::$static_routes as $key => $val) {
            if ($key === $url) {
                return true;
            }
        }

        $url = preg_replace(self::GET_FORMAT, '{}', $url);

        foreach (self::$dynamic_routes as $key => $val) {
            if ($url === preg_replace(self::GET_FORMAT, '{}', $key)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Returns true if a string has the format of a GET variable, false otherwise
     *
     * @param  string  $str  the string
     *
     * @return bool true if the string has the format of a route GET variable, false otherwise
     */
    private static function isGet(string $str): bool
    {
        return preg_match(self::GET_FORMAT, $str);
    }


    /**
     * Returns true if a string has the format of an optional GET variable, false otherwise
     *
     * @param  string  $str  the string
     *
     * @return bool true if the string has the format of an optional route GET variable, false otherwise
     */
    private static function isOptionalGet(string $str): bool
    {
        return preg_match(self::OPTIONAL_GET_FORMAT, $str);
    }


    /**
     * Set a GET variable
     *
     * @param  string  $key  the variable key
     * @param  string  $value  the variable value
     */
    private static function setGet(string $key, string $value): void
    {
        $key = preg_replace(self::GET_FORMAT, '$1', $key);
        $_GET[$key] = $value;
    }


    /**
     * Set an optional GET variable
     *
     * @param  string  $key  the variable key
     * @param  string|null  $value  the variable value
     */
    private static function setOptionalGetVar(string $key, $value = null): void
    {
        $key = preg_replace(self::OPTIONAL_GET_FORMAT, '$1', $key);
        $_GET[$key] = $value ?? '';
    }


    /**
     * Returns all the available routes
     *
     * @return array the available routes
     */
    public static function getRoutes(): array
    {
        return array_merge(self::$static_routes, self::$dynamic_routes);
    }


    /**
     * Returns all the available redirects
     *
     * @return array the available redirects
     */
    public static function getRedirects(): array
    {
        return self::$redirects;
    }


    /**
     * Returns the redirection of the specified route
     *
     * @param  string  $url  the route url
     *
     * @return array|null the redirection url
     * or null if the specified route doesn't have a redirection
     */
    public static function getRedirection(string $url): ?array
    {
        if (empty(self::$redirects)) {
            return null;
        }

        $url = explode('/', $url);
        $url_len = count($url) - 1;

        foreach (self::$redirects as $redirect) {
            if (Helper::matchesRoute($redirect['from'], $url, $url_len)) {
                return $redirect;
            }
        }

        return null;
    }


    /**
     * Returns all the blocked routes
     *
     * @return array the blocked routes
     */
    public static function getBlocked(): array
    {
        return self::$blocked;
    }
}
