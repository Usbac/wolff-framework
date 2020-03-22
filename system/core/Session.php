<?php

namespace Core;

class Session
{

    const DATE_FORMAT = 'Y-m-d H:i:s';


    /**
     * Destroy and unset the session if the live time is zero or less
     */
    private static function index()
    {
        if (self::hasExpired()) {
            self::empty();
            self::kill();

            return;
        }

        if (!self::isValid()) {
            self::initialize();
        }

        self::unsetExpiredVariables();
    }


    /**
     * Returns true if the current session has expired, false otherwise
     * @return bool true if the current session has expired, false otherwise
     */
    public static function hasExpired()
    {
        return isset($_SESSION['end_time']) && time() >= $_SESSION['end_time'];
    }


    /**
     * Returns true if the current IP and the userAgent are the same
     * than the IP and userAgent of the previous connection.
     * This is done for preventing session hijacking.
     *
     * @return bool true if the current IP address and the userAgent are the same
     * than the IP address and userAgent of the previous connection.
     */
    private static function isValid()
    {
        if (!isset($_SESSION['IPaddress']) || !isset($_SESSION['userAgent'])) {
            return false;
        }

        if ($_SESSION['IPaddress'] != getClientIP()) {
            return false;
        }

        if ($_SESSION['userAgent'] != getUserAgent()) {
            return false;
        }

        return true;
    }


    /**
     * Initialize all the session variables
     */
    private static function initialize()
    {
        self::empty();

        $_SESSION['IPaddress'] = getClientIP();
        $_SESSION['userAgent'] = getUserAgent();
        $_SESSION['start_time'] = microtime(true);
        $_SESSION['vars_tmp_time'] = [];
    }


    /**
     * Unset all the session variables that have expired
     */
    private static function unsetExpiredVariables()
    {

        if (!isset($_SESSION['vars_tmp_time']) || !is_array($_SESSION['vars_tmp_time'])) {
            $_SESSION['vars_tmp_time'] = [];
        }

        foreach ($_SESSION['vars_tmp_time'] as $key => $value) {
            if (time() >= $value) {
                self::unset($key);
            }
        }
    }


    /**
     * Starts the session and initializes everything
     */
    public static function start()
    {
        session_start();
        self::index();
    }


    /**
     * Returns a session variable
     *
     * @param  string  $key  the variable key
     *
     * @return mixed the session variable
     */
    public static function get(string $key = null)
    {
        if (!isset($key)) {
            return $_SESSION;
        }

        if (!self::has($key)) {
            return null;
        }

        return $_SESSION[$key];
    }


    /**
     * Set a session variable
     *
     * @param  string  $key  the variable key
     * @param  mixed  $value the variable value
     * @param  int  $time the variable live time in minutes
     */
    public static function set(string $key, $value, int $time = null)
    {
        $_SESSION[$key] = $value;

        if (isset($time)) {
            self::setVarTime($key, $time);
        } else {
            unset($_SESSION['vars_tmp_time'][$key]);
        }
    }


    /**
     * Returns true if the session variable exists, false otherwise
     *
     * @param  string  $key  the variable key
     *
     * @return bool true if the session variable exists, false otherwise
     */
    public static function has(string $key)
    {
        return array_key_exists($key, $_SESSION);
    }


    /**
     * Returns the numbers of elements in the session
     *
     * @return int the numbers of elements in the session
     */
    public static function count()
    {
        return count($_SESSION);
    }


    /**
     * Returns a live time (in minutes) of a session variable
     *
     * @param  string  $key  the variable key
     * @param  bool  $gmdate  return the time in date format
     *
     * @return int the variable live time
     */
    public static function getVarTime(string $key, bool $gmdate = false)
    {
        $remaining = 0;
        if (isset($_SESSION['vars_tmp_time'][$key])) {
            $remaining = $_SESSION['vars_tmp_time'][$key] - time();
        } else {
            Log::notice("Undefined '$key' live time value in Session array");
        }

        if ($gmdate) {
            return gmdate(self::DATE_FORMAT, ($remaining > 0) ? $remaining : 0);
        }

        return ($remaining > 0) ? $remaining : 0;
    }


    /**
     * Set a live time (in minutes) to a session variable
     *
     * @param  string  $key  the variable key
     * @param  int  $time  the variable live time in minutes
     */
    public static function setVarTime(string $key, int $time = 1)
    {
        if (!isset($_SESSION[$key])) {
            return;
        }

        $_SESSION['vars_tmp_time'][$key] = time() + ($time * 60);
    }


    /**
     * Add more live time (in minutes) to a session variable
     *
     * @param  string  $key  the variable key
     * @param  int  $time  the variable time to add
     */
    public static function addVarTime(string $key, int $time = 1)
    {
        $_SESSION['vars_tmp_time'][$key] += ($time * 60);
    }


    /**
     * Add time to the session live time (in minutes)
     *
     * @param  int  $time  the session live time to add
     */
    public static function addTime(int $time)
    {
        $_SESSION['end_time'] += $time * 60;
    }


    /**
     * Set the session live time (in minutes) starting from
     * the moment this function is called
     *
     * @param  int  $time  the time
     */
    public static function setTime(int $time)
    {
        $_SESSION['live_time'] = ($time * 60);
        $_SESSION['end_time'] = time() + ($time * 60);
    }


    /**
     * Returns the session creation time
     *
     * @param  bool  $gmdate  format the time in H:i:s
     *
     * @return mixed the session creation time
     */
    public static function getStartTime(bool $gmdate = false)
    {
        if ($gmdate) {
            return gmdate(self::DATE_FORMAT, $_SESSION['start_time']);
        }

        return $_SESSION['start_time'];
    }


    /**
     * Returns the time since the session was created in seconds
     * @return mixed the time since the session was created in seconds
     */
    public static function getPassedTime()
    {
        return microtime(true) - $_SESSION['start_time'];
    }


    /**
     * Returns the established session live time (in minutes)
     *
     * @param  bool  $gmdate  format the time in H:i:s
     *
     * @return mixed the established session live time (in minutes)
     */
    public static function getLiveTime(bool $gmdate = false)
    {
        if ($gmdate) {
            return gmdate(self::DATE_FORMAT, $_SESSION['live_time']);
        }

        return $_SESSION['live_time'];
    }


    /**
     * Returns the remaining session live time (in minutes)
     *
     * @param  bool  $gmdate  format the time in H:i:s
     *
     * @return mixed the remaining session live time (in minutes)
     */
    public static function getRemainingTime(bool $gmdate = false)
    {
        $end = $_SESSION['end_time'] ?? 0;
        $remaining = $end - time();

        if ($gmdate) {
            return gmdate(self::DATE_FORMAT, ($remaining > 0) ? $remaining : 0);
        }

        return ($remaining > 0) ? $remaining : 0;
    }


    /**
     * Unset a session variable
     *
     * @param  string  $key  the variable key
     */
    public static function unset(string $key)
    {
        unset($_SESSION[$key]);
        unset($_SESSION['vars_tmp_time'][$key]);
    }


    /**
     * Unset the session data
     */
    public static function empty()
    {
        session_unset();
    }


    /**
     * Destroy the session
     */
    public static function kill()
    {
        session_destroy();
    }

}
