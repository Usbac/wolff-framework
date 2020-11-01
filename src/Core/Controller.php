<?php

namespace Wolff\Core;

use ReflectionMethod;
use BadMethodCallException;

class Controller
{

    const NAMESPACE = 'Controller\\';
    const ERROR_EXISTS = 'The controller class \'%s\' or its method \'%s\'does not exists';


    /**
     * Returns the controller with the giving name
     *
     * @param  string|null  $path  the controller path
     *
     * @return \Wolff\Core\Controller the controller
     */
    public static function get(string $path = null)
    {
        return isset($path) ?
            self::getController($path) :
            new Controller;
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
    public static function method(string $path, string $method, array $args = [])
    {
        $controller = self::getController($path);

        if (!method_exists($controller, $method)) {
            throw new BadMethodCallException(
                sprintf(self::ERROR_EXISTS, $path, $method)
            );
        }

        return call_user_func_array([ $controller, $method ], $args);
    }


    /**
     * Returns true if the controller exists, false otherwise
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
     * Returns true if the controller's method exists and is public,
     * false otherwise
     *
     * @param  string  $path  the controller path
     * @param  string  $method  the controller method name
     *
     * @return bool true if the controller's method exists and is public,
     * false otherwise
     */
    public static function hasMethod(string $path, string $method): bool
    {
        $class = self::getClassname($path);

        return method_exists($class, $method) &&
            (new ReflectionMethod($class, $method))->isPublic();
    }


    /**
     * Returns the controller classname of the given path
     *
     * @param  string  $path  the controller path
     *
     * @return string the controller classname of the given path
     */
    private static function getClassname(string $path): string
    {
        return self::NAMESPACE . str_replace('/', '\\', $path);
    }


    /**
     * Returns a controller initialized
     *
     * @param  string  $path  the controller path
     *
     * @return object|null a controller initialized
     */
    private static function getController(string $path)
    {
        $class = self::getClassname($path);

        return class_exists($class) ? new $class : null;
    }
}
