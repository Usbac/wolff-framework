<?php

namespace Wolff\Core;

use Wolff\Utils\Str;
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
     * @param  array  $data  the data to connect to the database
     * @param  array  $options  the connection options
     *
     * @return PDO a PDO connection
     */
    public static function connection(array $data, array $options)
    {
        if (empty($options) || empty($data['db'])) {
            return null;
        }

        $dsn = sprintf(self::DSN, $data['dbms'] ?? '', $data['server'] ?? '', $data['db'] ?? '');
        $username = $data['db_username'] ?? '';
        $password = $data['db_password'] ?? '';

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
     * @param  string  $dir  the controller directory
     *
     * @return object|bool a controller initialized
     */
    public static function controller(string $dir = null)
    {
        //Load default Controller
        if (!isset($dir)) {
            return new Controller;
        }

        $class = self::NAMESPACE_CONTROLLER . str_replace('/', '\\', $dir);

        if (!class_exists($class)) {
            return false;
        }

        return new $class;
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
}
