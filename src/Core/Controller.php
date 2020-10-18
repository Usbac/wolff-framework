<?php

namespace Wolff\Core;

use Wolff\Utils\Str;
use Wolff\Exception\BadControllerCallException;

class Controller
{

    const NAMESPACE = 'Controller\\';
    const ERROR_CONTROLLER_EXISTS = 'The controller class \'%s\' does not exists';
    const ERROR_METHOD_EXISTS = 'The controller class \'%s\' does not have a \'%s\' method';


    /**
     * Returns the controller with the giving name
     *
     * @param  string  $path  the controller path
     *
     * @return \Wolff\Core\Controller the controller
     */
    public static function get(string $path)
    {
        $path = Str::sanitizePath($path);

        if (($controller = self::getController($path)) === null) {
            throw new BadControllerCallException(self::ERROR_CONTROLLER_EXISTS, $path);
        }

        return $controller;
    }


    /**
     * Returns the return value of the controller's method
     * or null in case of errors
     *
     * @param  string  $path  the controller path
     * @param  string  $method  the controller method
     * @param  array  $args  the method arguments
     *
     * @return mixed the return value of the controller's method
     * or null in case of errors
     */
    public static function method(string $path, string $method = 'index', array $args = [])
    {
        $controller = self::getController($path);

        if (!method_exists($controller, $method)) {
            throw new BadControllerCallException(self::ERROR_METHOD_EXISTS, $path, $method);
        }

        return call_user_func_array([ $controller, $method ], $args);
    }


    /**
     * Returns true if the controller exists,
     * false otherwise
     *
     * @param  string  $path  the path of the controller
     *
     * @return bool true if the controller exists, false otherwise
     */
    public static function exists(string $path): bool
    {
        return class_exists(self::getClassname($path));
    }


    /**
     * Returns true if the controller's method exists, false otherwise
     *
     * @param  string  $path  the controller path
     * @param  string  $method  the controller method name
     *
     * @return bool true if the controller's method exists, false otherwise
     */
    public static function hasMethod(string $path, string $method): bool
    {
        $path = self::getClassname($path);

        if (method_exists($path, $method)) {
            return (new \ReflectionMethod($path, $method))->isPublic();
        }

        return false;
    }


    /**
     * Returns the controller classname of the given path
     *
     * @return string The controller classname of the given path
     */
    private static function getClassname(string $path): string
    {
        return self::NAMESPACE . str_replace('/', '\\', $path);
    }


    /**
     * Returns a controller initialized
     *
     * @param  string|null  $path  the controller path
     *
     * @return object|null a controller initialized
     */
    private static function getController(string $path = null)
    {
        if (!isset($path)) {
            return new Controller;
        }

        $class = self::getClassname($path);

        return class_exists($class) ? new $class : null;
    }
}
