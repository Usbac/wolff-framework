<?php

namespace Wolff\Core;

use Wolff\Core\Helper;
use Wolff\Exception\FileNotReadableException;

final class Config
{

    const ENV_LINE_REGEX = "/'(.*)'|\"(.*)\"|(^[^#]+)/";

    /**
     * List of configuration variables
     *
     * @var array
     */
    private static $data = [];


    /**
     * Initializes the configuration data
     *
     * @param  array  $data  the configuration data
     *
     * @return array the configuration data
     */
    public static function init(array $data): array
    {
        self::$data = $data;

        if (empty($data['env']['file'])) {
            return self::$data;
        }

        self::parseEnv(Helper::getRoot($data['env']['file']));

        if ($data['env']['override'] ?? false) {
            foreach ($_ENV as $key => $val) {
                self::$data[strtolower($key)] = $val;
            }
        }

        return self::$data;
    }


    /**
     * Returns all the configuration or the specified key
     *
     * @param  string  $key  the key of the configuration array to get
     *
     * @return mixed all the configuration or the specified key
     */
    public static function get(string $key = null)
    {
        return Helper::val(self::$data, $key);
    }


    /**
     * Maps the variables from the given environment file path
     *
     * @param  string  $file_path  the environment file path
     */
    private static function parseEnv(string $file_path): void
    {
        if (!is_readable($file_path)) {
            throw new FileNotReadableException($file_path);
        }

        foreach (explode(PHP_EOL, file_get_contents($file_path)) as $line) {
            self::parseEnvLine($line);
        }
    }


    /**
     * Parses the given line and sets an environment variable from it
     *
     * @param  string  $line  the line
     */
    private static function parseEnvLine(string $line): void
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
     * Returns the value represented by the given string
     *
     * @param  string  $val  the string
     *
     * @return mixed the value represented by the given string
     */
    private static function getVal(string $val)
    {
        switch ($val) {
            case 'true': return true;
            case 'false': return false;
            case 'null': return null;
            case 'empty': return '';
            default: return Helper::removeQuotes($val);
        }
    }
}
