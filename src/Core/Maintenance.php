<?php

namespace Wolff\Core;

use Wolff\Core\Helper;

final class Maintenance
{

    /**
     * The maintenance status
     *
     * @var bool
     */
    private static $enabled = false;

    /**
     * IPs whitelist
     *
     * @var array
     */
    private static $ips = [];

    /**
     * Function to execute in maintenance mode
     *
     * @var \Closure
     */
    private static $func;


    /**
     * Sets the function to execute in maintenance mode.
     *
     * @param  \Closure  $func  the function
     */
    public static function set(\Closure $func): void
    {
        self::$func = $func;
    }


    /**
     * Returns true if the maintenance mode is enabled, false otherwise
     *
     * @return bool true if the maintenance mode is enabled, false otherwise
     */
    public static function isEnabled(): bool
    {
        return self::$enabled;
    }


    /**
     * Sets the maintenance status
     *
     * @param  bool  $enabled  true for enabling the maintenance mode,
     * false for disabling it
     */
    public static function setStatus(bool $enabled): void
    {
        self::$enabled = $enabled;
    }


    /**
     * Sets the IPs whitelist.
     *
     * @param  iterable  $ips  the IPs list
     */
    public static function setIPs(iterable $ips): void
    {
        foreach ($ips as $ip) {
            self::$ips[] = strval($ip);
        }
    }


    /**
     * Returns an array of the IPs in the whitelist
     *
     * @return array an array of the IPs in the whitelist
     */
    public static function getIPs(): array
    {
        return self::$ips;
    }


    /**
     * Removes the given IP from the whitelist
     *
     * @param  string  $ip  the IP to remove
     */
    public static function removeIP(string $ip): void
    {
        Helper::arrayRemove(self::$ips, $ip);
    }


    /**
     * Returns true if the current client IP is in the whitelist,
     * false otherwise
     *
     * @return bool true if the current client IP is in the whitelist,
     * false otherwise
     */
    public static function hasAccess(): bool
    {
        return in_array(Helper::getClientIP(), self::$ips);
    }


    /**
     * Loads the maintenance page
     *
     * @param \Wolff\Core\Http\Request $req Reference to the request object
     * @param \Wolff\Core\Http\Response $res Reference to the response object
     */
    public static function call(Http\Request &$req, Http\Response &$res): void
    {
        if (isset(self::$func)) {
            call_user_func_array(self::$func, [ $req, $res ]);
        }
    }
}
