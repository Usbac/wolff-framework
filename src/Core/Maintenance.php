<?php

namespace Wolff\Core;

use Wolff\Core\Helper;
use Wolff\Exception\FileNotReadableException;

final class Maintenance
{

    const DEFAULT_FILE = 'system/maintenance_whitelist.txt';

    /**
     * The maintenance status
     * @var bool
     */
    private static $enabled = false;

    /**
     * Filename of the ip whitelist file.
     *
     * @var string
     */
    private static $file = null;

    /**
     * Function to execute in maintenance mode.
     *
     * @var \Closure
     */
    private static $function;


    /**
     * Sets the function to execute in maintenance mode.
     *
     * @param  \Closure  $function  the function
     */
    public static function set(\Closure $function)
    {
        self::$function = $function;
    }


    /**
     * Sets the ip whitelist file.
     *
     * @param  string  $path  the file path
     */
    public static function setFile(string $path = self::DEFAULT_FILE)
    {
        self::$file = Helper::getRoot($path);
    }


    /**
     * Returns true if the maintenance mode is enabled, false otherwise
     * @return bool true if the maintenance mode is enabled, false otherwise
     */
    public static function isEnabled()
    {
        return self::$enabled;
    }


    /**
     * Sets the maintenance status
     *
     * @param  bool  $enabled  True for enabling the maintenance mode,
     * false for disabling it
     */
    public static function setStatus(bool $enabled)
    {
        self::$enabled = $enabled;
    }


    /**
     * Returns an array of the IPs in the whitelist
     *
     * @throws \Wolff\Exception\FileNotReadableException
     *
     * @return array|false An array of the IPs in the whitelist
     */
    public static function getAllowedIPs()
    {
        if (!isset(self::$file)) {
            self::setFile();
        }

        if (!is_file(self::$file)) {
            return false;
        } elseif (!is_readable(self::$file)) {
            throw new FileNotReadableException(self::$file);
        } else {
            $content = file_get_contents(self::$file);
        }

        return explode(PHP_EOL, $content);
    }


    /**
     * Adds an IP to the whitelist
     *
     * @throws \Wolff\Exception\FileNotReadableException
     *
     * @param  string  $ip  the IP to add
     *
     * @return bool true if the IP has been added, false otherwise
     */
    public static function addAllowedIP(string $ip)
    {
        if (!isset(self::$file)) {
            self::setFile();
        }

        if (!($ip = filter_var($ip, FILTER_VALIDATE_IP))) {
            return false;
        }

        self::createFile();

        if (!file_get_contents(self::$file)) {
            if (is_writable(self::$file)) {
                file_put_contents(self::$file, $ip);

                return true;
            }

            throw new FileNotReadableException(self::$file);
        }

        file_put_contents(self::$file, PHP_EOL . $ip, FILE_APPEND | LOCK_EX);

        return true;
    }


    /**
     * Removes an IP from the whitelist
     *
     * @throws \Wolff\Exception\FileNotReadableException
     *
     * @param  string  $ip  the IP to remove
     *
     * @return bool true if the IP has been removed, false otherwise
     */
    public static function removeAllowedIP(string $ip)
    {
        if (!isset(self::$file)) {
            self::setFile();
        }

        if (!($ip = filter_var($ip, FILTER_VALIDATE_IP))) {
            return false;
        } elseif (!is_readable(self::$file)) {
            throw new FileNotReadableException(self::$file);
        } else {
            $content = file_get_contents(self::$file);
        }

        $ip_list = array_filter(explode(PHP_EOL, $content));
        Helper::arrayRemove($ip_list, $ip);
        file_put_contents(self::$file, implode(PHP_EOL, $ip_list));

        return true;
    }


    /**
     * Create the text file with the IP whitelist
     */
    private static function createFile()
    {
        if (!is_file(self::$file)) {
            file_put_contents(self::$file, '');
        }
    }


    /**
     * Returns true if the current client IP is in the whitelist, false otherwise
     * @return bool true if the current client IP is in the whitelist, false otherwise
     */
    public static function hasAccess()
    {
        if (($ips = self::getAllowedIPs()) === false) {
            return false;
        }

        return in_array(Helper::getClientIP(), $ips);
    }


    /**
     * Loads the maintenance page
     *
     * @param \Wolff\Core\Http\Request $req Reference to the current request object
     * @param \Wolff\Core\Http\Response $res Reference to the current response object
     */
    public static function call(Http\Request &$req, Http\Response &$res)
    {
        if (isset(self::$function)) {
            call_user_func_array(self::$function, [
                $req,
                $res
            ]);
        }
    }
}
