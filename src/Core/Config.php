<?php

namespace Wolff\Core;

use Wolff\Exception\FileNotReadableException;

final class Config
{

    const DEFAULT_ENV = '.env';

    /**
     * List of configuration variables
     * @var array
     */
    private static $data = [];


    /**
     * Path of the environment file
     * @var string
     */
    private static $env_path;


    /**
     * Initializes the configuration data
     */
    public static function init(array $data)
    {
        $env_path = isset($data['env_file']) ?
            $data['env_file'] :
            self::DEFAULT_ENV;
        self::$env_path = Helper::getRoot($env_path);
        self::$data = $data;

        if (!isset($data['env_file'])) {
            return;
        }

        self::parseEnv();

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
     * Maps the environment variables from an existing env file.
     * This is Wolff's own parser, an existing one has not been used
     * because lol
     */
    public static function parseEnv()
    {
        if (($content = file_get_contents(self::$env_path)) === false) {
            throw new FileNotReadableException(self::$env_path);
        }

        $lines = explode(PHP_EOL, $content);
        foreach ($lines as $line) {
            if (!($index_equal = strpos($line, '='))) {
                continue;
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

        $len = strlen($val) - 1;
        if (($val[0] === '\'' && $val[$len] === '\'') ||
            ($val[0] === '"' && $val[$len] === '"')) {
            $val = substr($val, 1, $len - 1);
        }

        return $val;
    }
}
