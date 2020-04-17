<?php

namespace Wolff\Core;

use Wolff\Core\Helper;
use Wolff\Utils\Str;

/**
 * @method static emergency(string $message, array $values = [])
 * @method static alert(string $message, array $values = [])
 * @method static critical(string $message, array $values = [])
 * @method static error(string $message, array $values = [])
 * @method static warning(string $message, array $values = [])
 * @method static notice(string $message, array $values = [])
 * @method static info(string $message, array $values = [])
 * @method static debug(string $message, array $values = [])
 */
final class Log
{

    const FOLDER_PERMISSIONS = 0755;
    const FILENAME_FORMAT = '%s.log';
    const DEFAULT_FOLDER = 'system/logs/';
    const DEFAULT_DATE_FORMAT = 'H:i:s';
    const MSG_FORMAT = '[%s][%s] %s: %s';
    const LEVELS = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug'
    ];

    /**
     * The log status
     * @var bool
     */
    private static $enabled = true;

    /**
     * The log folder
     * @var string
     */
    private static $folder = self::DEFAULT_FOLDER;

    /**
     * The date format used internally in the files
     * @var string
     */
    private static $date_format = self::DEFAULT_DATE_FORMAT;


    /**
     * Sets the log system status
     *
     * @param  bool  $enabled  True for enabling the log system,
     * false for disabling it
     */
    public static function setStatus(bool $enabled = true)
    {
        self::$enabled = $enabled;
    }


    /**
     * Returns true if the log is enabled, false otherwise
     * @return bool true if the log is enabled, false otherwise
     */
    public static function isEnabled()
    {
        return self::$enabled;
    }


    /**
     * Sets the log folder
     *
     * @param  string  $folder  the log folder
     */
    public static function setFolder(string $folder = self::DEFAULT_FOLDER)
    {
        self::$folder = $folder;
    }


    /**
     * Sets the date format used internally for the files
     *
     * @param  string  $date_format  the date format
     */
    public static function setDateFormat(string $date_format = self::DEFAULT_DATE_FORMAT)
    {
        self::$date_format = $date_format;
    }


    /**
     * Proxy method to log the messages in
     * different levels.
     *
     * @param  string  $method_name the method name
     * @param  mixed  $args  the method arguments
     */
    public static function __callStatic(string $method_name, $args)
    {
        //If the method is not for logging something or if the log message is empty
        if (!in_array($method_name, self::LEVELS) ||
            ($message = $args[0]) === null) {
            return;
        }

        $values = $args[1] ?? [];

        self::log(ucfirst($method_name), $message, $values);
    }


    /**
     * Logs a general message
     *
     * @param  string  $level the message level
     * @param  string  $message the message
     * @param  array  $values  the values to interpolate
     */
    private static function log(string $level, string $message, array $values)
    {
        if (!self::isEnabled()) {
            return;
        }

        $message = Str::interpolate($message, $values);
        $date = date(self::$date_format);
        $log = sprintf(self::MSG_FORMAT, $date, Helper::getClientIP(), $level, $message);
        self::mkdir();
        self::writeToFile($log);
    }


    /**
     * Creates the logs folder if it doesn't exists
     */
    private static function mkdir()
    {
        $folder_path = Helper::getRoot(self::$folder);

        if (!file_exists($folder_path)) {
            mkdir($folder_path, self::FOLDER_PERMISSIONS, true);
        }
    }


    /**
     * Writes content to the current log file
     *
     * @param  string  $data the content to append
     */
    private static function writeToFile(string $data)
    {
        file_put_contents(Helper::getRoot(self::getFilename()), $data . PHP_EOL, FILE_APPEND);
    }


    /**
     * Returns a log filename
     *
     * @return string the log filename
     */
    private static function getFilename()
    {
        return self::$folder . '/' . sprintf(self::FILENAME_FORMAT, date('m-d-Y'));
    }
}
