<?php

namespace Wolff\Core;

use Wolff\Exception\InvalidLanguageException;

final class Language
{

    const ERROR_FILE = 'The %s language file for \'%s\' must return an associative array';
    const PATH_FORMAT =  'app/languages/%s/%s.php';

    /**
     * The default language to use
     *
     * @var string
     */
    private static $default;


    /**
     * Sets the default language to use
     *
     * @param  string  $lang  the default language to use
     */
    public static function setDefault(string $lang): void
    {
        self::$default = $lang;
    }


    /**
     * Returns the content of a language, or null if
     * it doesn't exists
     *
     * @throws \Wolff\Exception\InvalidLanguageException
     *
     * @param  string  $dir  the language directory
     * @param  string|null  $lang  the language selected
     *
     * @return mixed the content of a language, or null if
     * it doesn't exists
     */
    public static function get(string $dir, string $lang = null)
    {
        $lang = $lang ?? self::$default;

        if (($dot_pos = strpos($dir, '.')) !== false) {
            $dir = substr($dir, 0, $dot_pos);
            $key = substr($dir, $dot_pos + 1);
        }

        if (!self::exists($dir, $lang)) {
            return null;
        }

        $data = (include self::getPath($dir, $lang));
        if (!is_array($data)) {
            throw new InvalidLanguageException(self::ERROR_FILE, $lang, $dir);
        }

        return !isset($key) ?
            $data :
            ($data[$key] ?? null);
    }


    /**
     * Returns the path of a language file
     *
     * @param  string  $dir  the language directory
     * @param  string  $lang  the language selected
     *
     * @return string the path of a language file
     */
    private static function getPath(string $dir, string $lang): string
    {
        return Helper::getRoot(sprintf(self::PATH_FORMAT, $lang, $dir));
    }


    /**
     * Returns true if the specified language exists,
     * false otherwise
     *
     * @param  string  $dir  the language directory
     * @param  string|null  $lang  the language selected
     *
     * @return bool true if the specified language exists,
     * false otherwise
     */
    public static function exists(string $dir, string $lang = null): bool
    {
        return file_exists(self::getPath($dir, $lang ?? self::$default));
    }
}
