<?php

namespace Wolff\Core;

use \Wolff\Exception\FileNotReadableException;

final class Config
{

    /**
     * List of configuration variables
     * @var array
     */
    private static $data = [];


    /**
     * Path of the environment file
     * @var string
     */
    private static $env_path = CONFIG['root_dir'] . '/.env';


    /**
     * Initializes the configuration data
     */
    public static function init()
    {
        if (is_string(CONFIG['env_file'] ?? null)) {
            self::$env_path = CONFIG['root_dir'] . '/' . CONFIG['env_file'];
        }

        self::$data = CONFIG;
        self::parseEnv();

        if (CONFIG['env_override'] ?? false) {
            array_merge(self::$data, $_ENV);
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
            throw new \FileNotReadableException(self::$env_path);
        }

        foreach ((explode(PHP_EOL, $content)) as $line) {
            if (!($index_equal = strpos($line, '='))) {
                continue;
            }

            $key = trim(substr($line, 0, $index_equal));
            $val = trim(substr($line, $index_equal + 1));
            // Anything between or not single/double quotes, excluding the hashtag character after it
            $val = preg_match("/'(.*)'|\"(.*)\"|(^[^#]+)/", $val, $matches);
            $val = trim($matches[0] ?? 'null');

            switch ($val) {
                case 'true':
                    $val = true;
                    break;
                case 'false':
                    $val = false;
                    break;
                case 'null':
                    $val = null;
                    break;
                case 'empty':
                    $val = '';
                    break;
                default:
                    $len = strlen($val) - 1;
                    if (($val[0] === '\'' && $val[$len] === '\'') ||
                        ($val[0] === '"' && $val[$len] === '"')) {
                        $val = substr($val, 1, $len - 1);
                    }
            }

            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
}
