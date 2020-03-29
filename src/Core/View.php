<?php

namespace Wolff\Core;

use Wolff\Utils\Str;

final class View
{

    /**
     * Renders a view
     *
     * @param  string  $dir  the view directory
     * @param  array  $data  the view data
     * @param  bool  $cache  use or not the cache system
     */
    public static function render(string $dir, array $data = [], bool $cache = true)
    {
        echo self::getRender($dir, $data, $cache);
    }


    /**
     * Returns the original view content
     *
     * @param  string  $dir  the view directory
     *
     * @return string the original view content
     */
    public static function getSource(string $dir)
    {
        $dir = Str::sanitizePath($dir);
        return file_get_contents(self::getPath($dir));
    }


    /**
     * Returns the view content with the template format applied
     * over it
     *
     * @param  string  $dir  the view directory
     * @param  array  $data  the data
     * @param  bool  $cache  use or not the cache system
     *
     * @return mixed the view content with the template format applied
     * over it
     */
    public static function get(string $dir, array $data = [], bool $cache = true)
    {
        $dir = Str::sanitizePath($dir);
        return Template::get($dir, $data, $cache);
    }


    /**
     * Returns a view content fully rendered
     *
     * @param  string  $dir  the view directory
     * @param  array  $data  the data
     * @param  bool  $cache  use or not the cache system
     *
     * @return string the view content fully rendered
     */
    public static function getRender(string $dir, array $data = [], bool $cache = true)
    {
        $dir = Str::sanitizePath($dir);
        return Template::getRender($dir, $data, $cache);
    }


    /**
     * Returns the complete view file path
     *
     * @param  string  $dir  the view directory
     *
     * @return string the complete view file path
     */
    public static function getPath(string $dir)
    {
        return Template::getPath($dir);
    }


    /**
     * Returns true if the given view exists,
     * false otherwise
     *
     * @param  string  $dir  the directory of the view
     *
     * @return boolean true if the view exists, false otherwise
     */
    public static function exists(string $dir)
    {
        return file_exists(self::getPath($dir));
    }
}
