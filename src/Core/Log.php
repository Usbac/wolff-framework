<?php

namespace Wolff\Core;

use Wolff\Utils\Str;

/**
 * @method emergency(string $message, array $values = [])
 * @method alert(string $message, array $values = [])
 * @method critical(string $message, array $values = [])
 * @method error(string $message, array $values = [])
 * @method warning(string $message, array $values = [])
 * @method notice(string $message, array $values = [])
 * @method info(string $message, array $values = [])
 * @method debug(string $message, array $values = [])
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
        'debug',
    ];

    /**
     * The log status
     * @var bool
     */
    private $enabled = true;

    /**
     * The log folder
     * @var string
     */
    private $folder = self::DEFAULT_FOLDER;

    /**
     * The date format used internally in the files
     * @var string
     */
    private $date_format = self::DEFAULT_DATE_FORMAT;


    /**
     * Sets the log system status
     *
     * @param  bool  $enabled  true for enabling the log system,
     * false for disabling it
     */
    public function setStatus(bool $enabled = true): void
    {
        $this->enabled = $enabled;
    }


    /**
     * Returns true if the log is enabled, false otherwise
     *
     * @return bool true if the log is enabled, false otherwise
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }


    /**
     * Sets the log folder
     *
     * @param  string  $folder  the log folder
     */
    public function setFolder(string $folder = self::DEFAULT_FOLDER): void
    {
        $this->folder = Helper::getRoot($folder);
    }


    /**
     * Sets the date format used internally for the files
     *
     * @param  string  $date_format  the date format
     */
    public function setDateFormat(string $date_format = self::DEFAULT_DATE_FORMAT): void
    {
        $this->date_format = $date_format;
    }


    /**
     * Proxy method to log the messages in
     * different levels
     *
     * @param  string  $method_name the method name
     * @param  mixed  $args  the method arguments
     */
    public function __call(string $method_name, $args)
    {
        if (in_array($method_name, self::LEVELS) && isset($args[0])) {
            $this->log(ucfirst($method_name), $args[0], $args[1] ?? []);
        }
    }


    /**
     * Logs a general message
     *
     * @param  string  $level the message level
     * @param  string  $message the message
     * @param  array  $values  the values to interpolate
     */
    private function log(string $level, string $message, array $values): void
    {
        if (!$this->enabled) {
            return;
        }

        $log = sprintf(self::MSG_FORMAT,
            date($this->date_format),
            Helper::getClientIP(),
            $level,
            Str::interpolate($message, $values));

        $this->mkdir();
        $this->writeToFile($log);
    }


    /**
     * Creates the logs folder if it doesn't exists
     */
    private function mkdir(): void
    {
        if (!file_exists($this->folder)) {
            mkdir($this->folder, self::FOLDER_PERMISSIONS, true);
        }
    }


    /**
     * Writes content to the current log file
     *
     * @param  string  $data the content to append
     */
    private function writeToFile(string $data): void
    {
        file_put_contents($this->getFilename(), $data . PHP_EOL, FILE_APPEND);
    }


    /**
     * Returns a log filename
     *
     * @return string the log filename
     */
    private function getFilename(): string
    {
        return $this->folder . '/' . sprintf(self::FILENAME_FORMAT, date('m-d-Y'));
    }
}
