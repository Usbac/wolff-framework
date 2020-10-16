<?php

namespace Wolff\Core;

use Wolff\Exception\FileNotFoundException;

final class Cache
{

    const EXISTS_ERROR = 'Cache file \'%s\' doesn\'t exists';
    const FOLDER = 'cache';
    const FILENAME_FORMAT = '%s.tmp';
    const EXPIRATION_TIME = 604800; //One week
    const FOLDER_PERMISSIONS = 0755;

    /**
     * The cache status
     * @var bool
     */
    private static $enabled = true;


    /**
     * Deletes all the cache files that have expired
     *
     * @param  bool  $enabled  the cache status
     */
    public static function init(bool $enabled = true)
    {
        self::$enabled = $enabled;

        if (!self::$enabled) {
            return;
        }

        $files = glob(self::getDir('*.php'));
        $time = time();

        foreach ($files as $file) {
            $modification_time = filemtime($file);

            if ($modification_time !== false &&
                $time - $modification_time > self::EXPIRATION_TIME) {
                unlink($file);
            }
        }
    }


    /**
     * Returns true if the cache is enabled, false otherwise
     * @return bool true if the cache is enabled, false otherwise
     */
    public static function isEnabled()
    {
        return self::$enabled;
    }


    /**
     * Returns the content of the cache file
     *
     * @throws \Wolff\Exception\FileNotFoundException
     *
     * @param  string  $dir  the cache filename
     *
     * @return string return the content of the cache file
     */
    public static function get(string $dir)
    {
        $file_path = self::getDir(self::getFilename($dir));

        if (file_exists($file_path)) {
            return file_get_contents($file_path);
        }

        throw new FileNotFoundException(
            sprintf(self::EXISTS_ERROR, $file_path)
        );
    }


    /**
     * Creates the cache file if doesn't exists and return its path
     *
     * @param  string  $dir  the cache filename
     * @param  string  $content  the original file content
     *
     * @return string the cache file path
     */
    public static function set(string $dir, string $content)
    {
        $file_path = self::getDir(self::getFilename($dir));

        if (!file_exists($file_path)) {
            self::mkdir();
            $file = fopen($file_path, 'w');
            fwrite($file, $content);
            fclose($file);
        }

        return $file_path;
    }


    /**
     * Creates the cache folder if it doesn't exists
     */
    public static function mkdir()
    {
        if (!file_exists(self::getDir())) {
            mkdir(self::getDir(), self::FOLDER_PERMISSIONS, true);
        }
    }


    /**
     * Checks if the specified cache exists
     *
     * @param  string  $dir  the cache file directory
     *
     * @return bool true if the cache exists, false otherwise
     */
    public static function has(string $dir)
    {
        return is_file(self::getDir(self::getFilename($dir)));
    }


    /**
     * Deletes the specified cache file
     *
     * @param  string  $dir  the cache to delete
     *
     * @return bool true if the item was successfully removed, false otherwise
     */
    public static function delete(string $dir)
    {
        $file_path = self::getDir(self::getFilename($dir));

        if (is_file($file_path)) {
            unlink($file_path);

            return true;
        }

        return false;
    }


    /**
     * Deletes all of the cache files
     */
    public static function clear()
    {
        $files = glob(self::getDir() . '/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }


    /**
     * Returns the cache format name of a file
     *
     * @param  string  $dir  the cache file
     *
     * @return string the cache format name of a file
     */
    private static function getFilename(string $dir)
    {
        return sprintf(self::FILENAME_FORMAT, str_replace('/', '_', $dir));
    }


    /**
     * Returns the cache directory of the project
     *
     * @param  string  $path  the optional path to append
     *
     * @return string the cache directory of the project
     */
    private static function getDir(string $path = '')
    {
        return Helper::getRoot(self::FOLDER . '/' . $path);
    }
}
