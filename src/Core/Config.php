<?php

namespace Wolff\Core;

use Wolff\Core\Helper;
use Wolff\Exception\FileNotReadableException;

final class Config
{

    const ENV_LINE_REGEX = "/'(.*)'|\"(.*)\"|(^[^#]+)/";

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
        return !isset($key) ?
            self::$data :
            self::$data[$key];
    }


    /**
     * Maps the environment variables from the given environment file path.
     * This is Wolff's own parser, an existing one has not been used
     * because lol
     *
     * @param  string  $file_path  the environment file path
     */
    private static function parseEnv(string $file_path)
    {
        if (is_readable($file_path)) {
            $content = file_get_contents($file_path);
        } else {
            throw new FileNotReadableException($file_path);
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
        if (!($equal_pos = strpos($line, '='))) {
            return;
        }

        $key = trim(substr($line, 0, $equal_pos));
        $val = trim(substr($line, $equal_pos + 1));
        preg_match(self::ENV_LINE_REGEX, $val, $matches);
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
            case 'true': return true;
            case 'false': return false;
            case 'null': return null;
            case 'empty': return '';
            default: Helper::removeQuotes($val);
        }
    }
}
