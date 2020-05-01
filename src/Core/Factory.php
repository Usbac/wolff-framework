<?php

namespace Wolff\Core;

use PDO;
use PDOException;

final class Factory
{

    const NAMESPACE_CONTROLLER = 'Controller\\';
    const DSN = '%s:host=%s; dbname=%s';
    const DEFAULT_ENCODING = 'set names utf8mb4 collate utf8mb4_unicode_ci';


    /**
     * Returns a PDO connection
     *
     * @throws \PDOException
     *
     * @param  array  $data  the data to connect to the database
     * @param  array  $options  the connection options
     *
     * @return PDO|null the PDO connection
     */
    public static function connection(array $data, array $options)
    {
        if (empty($options) || !isset($data['name']) || empty($data['name'])) {
            return null;
        }

        $dsn = sprintf(self::DSN, $data['dbms'] ?? '', $data['server'] ?? '', $data['name']);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        try {
            $connection = new PDO($dsn, $username, $password, $options);
            $connection->prepare(self::DEFAULT_ENCODING)->execute();
        } catch (PDOException $err) {
            throw $err;
        }

        return $connection;
    }


    /**
     * Returns a controller initialized
     *
     * @param  string|null  $dir  the controller directory
     *
     * @return object|null a controller initialized
     */
    public static function controller(string $dir = null)
    {
        if (!isset($dir)) {
            return new Controller;
        }

        $class = self::NAMESPACE_CONTROLLER . str_replace('/', '\\', $dir);

        return class_exists($class) ? new $class : null;
    }


    /**
     * Returns a query result as an object
     *
     * @param \PDOStatement $results
     * @return Query a query result as an object
     */
    public static function query($results)
    {
        return new Query($results);
    }


    /**
     * Returns a new request object
     * based on the current request data
     *
     * @return  Http\Request  The new request object
     */
    public static function request()
    {
        return new Http\Request(
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER,
            $_COOKIE
        );
    }


    /**
     * Returns a new response object
     *
     * @return  Http\Response  The new response object
     */
    public static function response()
    {
        return new Http\Response();
    }
}
