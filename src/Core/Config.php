<?php

namespace Wolff\Core;

use Wolff\Core\Helper;
use Wolff\Exception\FileNotReadableException;

final class Config
{

    /**
     * List of configuration variables
     * @var array
     */
    private static $data = [];


    /**
     * Initializes the configuration data
     */
    public static function init(array $data)
    {
        self::$data = $data;

        if (!isset($data['env_file']) || empty($data['env_file'])) {
            return;
        }

        self::parseEnv(Helper::getRoot($data['env_file']));

        if ($data['env_override'] ?? false) {
            foreach ($_ENV as $key => $val) {
                self::$data[strtolower($key)] = $val;
            }
        }
    }


    /**
     * Returns the configuration
     */
    public static function get(string $key = null)
    {
        if (!isset($key)) {
            return self::$data;
        }

        return self::$data[$key];
    }


    /**
     * Maps the environment variables from the given environment file path.
     * This is Wolff's own parser, an existing one has not been used
     * because lol
     *
     * @param  string  $env_path  the environment file path
     */
    private static function parseEnv(string $env_path)
    {
        if (is_readable($env_path)) {
            $content = file_get_contents($env_path);
        } else {
            throw new FileNotReadableException($env_path);
        }

        array_map('self::parseEnvLine', explode(PHP_EOL, $content));
    }


    /**
     * Parses the given line and sets an environment variable from it
     *
     * @param  string  $line  the line
     */
    private static function parseEnvLine(string $line)
    {
        if (!($index_equal = strpos($line, '='))) {
            return;
        }

        $key = trim(substr($line, 0, $index_equal));
        $val = trim(substr($line, $index_equal + 1));
        // Anything between or not single/double quotes, excluding the hashtag character after it
        preg_match("/'(.*)'|\"(.*)\"|(^[^#]+)/", $val, $matches);
        $val = trim($matches[0] ?? 'null');
        $val = self::getVal($val);

        putenv("$key=$val");
        $_ENV[$key] = $val;
    }


    /**
     * Returns the true value of the given string
     *
     * @param  string  $val  The string
     *
     * @return mixed the true value
     */
    private static function getVal(string $val)
    {
        switch ($val) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'null':
                return null;
            case 'empty':
                return '';
        }

        return Helper::removeQuotes($val);
    }
}
