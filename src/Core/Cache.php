<?php

namespace Wolff\Core;

use Wolff\Exception\FileNotFoundException;

final class Cache
{

    const EXISTS_ERROR = 'Cache file \'%s\' doesn\'t exists';
    const FOLDER = 'cache';
    const FILE_EXT = 'tmp';
    const FILENAME_FORMAT = '%s.' . self::FILE_EXT;
    const FOLDER_PERMISSIONS = 0755;

    /**
     * The cache status
     * @var bool
     */
    private static $enabled = true;


    /**
     * Sets the cache status
     *
     * @param  bool  $enabled  True for enabling the cache system,
     * false for disabling it
     */
    public static function setStatus(bool $enabled = true): void
    {
        self::$enabled = $enabled;
    }


    /**
     * Returns true if the cache is enabled, false otherwise
     * @return bool true if the cache is enabled, false otherwise
     */
    public static function isEnabled(): bool
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
    public static function get(string $dir): string
    {
        $file_path = self::getFilename($dir);

        if (!file_exists($file_path)) {
            throw new FileNotFoundException(
                sprintf(self::EXISTS_ERROR, $file_path)
            );
        }

        return file_get_contents($file_path);
    }


    /**
     * Creates the cache file if doesn't exists and return its path
     *
     * @param  string  $dir  the cache filename
     * @param  string  $content  the original file content
     *
     * @return string the cache file path
     */
    public static function set(string $dir, string $content): string
    {
        $file_path = self::getFilename($dir);

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
    public static function mkdir(): void
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
    public static function has(string $dir): bool
    {
        return is_file(self::getFilename($dir));
    }


    /**
     * Deletes the specified cache file
     *
     * @param  string  $dir  the cache to delete
     *
     * @return bool true if the item was successfully removed, false otherwise
     */
    public static function delete(string $dir): bool
    {
        $file_path = self::getFilename($dir);

        if (is_file($file_path)) {
            return unlink($file_path);
        }

        return false;
    }


    /**
     * Deletes all of the cache files.
     * If no seconds parameter is given all files will be deleted.
     *
     * @param  int  $seconds  the minimum time in seconds
     * that a file needs to have since its last modification
     * to be deleted
     */
    public static function clear(int $seconds = 0): void
    {
        $time = time();

        foreach (glob(self::getDir('*.' . self::FILE_EXT)) as $file) {
            $mod_time = filemtime($file);

            if ($mod_time !== false && ($time - $mod_time) > $seconds) {
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
    private static function getFilename(string $dir): string
    {
        $filename = sprintf(self::FILENAME_FORMAT, str_replace('/', '_', $dir));
        return self::getDir($filename);
    }


    /**
     * Returns the cache directory of the project
     *
     * @param  string  $path  the optional path to append
     *
     * @return string the cache directory of the project
     */
    private static function getDir(string $path = ''): string
    {
        return Helper::getRoot(self::FOLDER . '/' . $path);
    }
}
