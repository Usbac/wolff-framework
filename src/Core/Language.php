<?php

namespace Wolff\Core;

use Wolff\Exception\InvalidLanguageException;

final class Language
{

    const BAD_FILE_ERROR = 'The %s language file for \'%s\' must return an associative array';
    const PATH_FORMAT = CONFIG['root_dir'] . '/app/languages/%s/%s.php';


    /**
     * Returns the content of a language, or null if
     * it doesn't exists
     *
     * @throws \Wolff\Exception\InvalidLanguageException
     *
     * @param  string  $dir  the language directory
     * @param  string|null  $language  the language selected
     *
     * @return mixed the content of a language, or null if
     * it doesn't exists
     */
    public static function get(string $dir, string $language = null)
    {
        if (!isset($language)) {
            $language = CONFIG['language'];
        }

        if (($dot_pos = strpos($dir, '.')) !== false) {
            $key = substr($dir, $dot_pos + 1);
            $dir = substr($dir, 0, $dot_pos);
        }

        if (self::exists($dir, $language)) {
            $data = (include self::getPath($dir, $language));
        } else {
            return null;
        }

        if (!is_array($data)) {
            throw new InvalidLanguageException(self::BAD_FILE_ERROR, $language, $dir);
        }

        if (isset($key)) {
            return $data[$key] ?? null;
        }

        return $data;
    }


    /**
     * Returns the path of a language file
     *
     * @param  string  $dir  the language directory
     * @param  string  $language  the language selected
     *
     * @return string the path of a language file
     */
    private static function getPath(string $dir, string $language)
    {
        return sprintf(self::PATH_FORMAT, $language, $dir);
    }


    /**
     * Returns true if the specified language exists,
     * false otherwise
     *
     * @param  string  $dir  the language directory
     * @param  string  $language  the language selected
     *
     * @return bool true if the specified language exists,
     * false otherwise
     */
    public static function exists(string $dir, string $language = CONFIG['language'])
    {
        return file_exists(self::getPath($dir, $language));
    }
}
