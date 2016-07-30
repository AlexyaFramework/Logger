<?php
namespace Alexya\Logger;

use \Alexya\FileSyste\{
    Directory,
    File
};

use \Psr\Log\{
    AbstractLogger,
    InvalidArgumentException,
    LogLevel
};

/**
 * Alexya's Logger.
 *
 * Implements a PSR compatible file logger.
 *
 * The constructor accepts as parameter the following parameters:
 *
 *  * The [\Alexya\FileSystem\File](../vendor/alexya-framework/filesystem/Alexya/FileSystem/File.php)
 *    object that will represent the path where the log files will be saved.
 *  * A string being the format of each log file name, you can add the followin placeholders:
 *      * `{YEAR}`, current year.
 *      * `{MONTH}`, current month.
 *      * `{DAY}`, current day.
 *      * `{HOUR}`, current hour.
 *      * `{MINUTE}`, current minute.
 *      * `{SERVER_NAME}`, server's name (`localhost`, `test.com`...).
 *  * A string being the format that each log entry will have, you can add the followin placeholders:
 *      * `{YEAR}`, current year.
 *      * `{MONTH}`, current month.
 *      * `{DAY}`, current day.
 *      * `{HOUR}`, current hour.
 *      * `{MINUTE}`, current minute.
 *      * `{SERVER_NAME}`, server's name (`localhost`, `test.com`...).
 *      * `{CALLING_FUNCTION}`, the function that called the logger.
 *      * `{CALLING_FILE}`, the file that called the logger.
 *      * `{CALLING_LINE}`, the line that called the logger.
 *      * `{CALLING_CLASS}`, the class that called the logger.
 *      * `{CALLING_TYPE}`, `->` if the logger was called by an object, `::` if it was called statically.
 *      * `{LEVEL}`, the level on which the log has been called.
 *      * `{LOG}`, the string to log
 *  * An array containing the elements that will be logged, you can get a full list
 *    of available values in the class [\Psr\Log\LogLevel](../vendor/psr/log/Psr/Log/LogLevel)
 *
 * The method `log` performs the actual loggin and accepts as parameter the log level
 * (see [\Psr\Log\LogLevel](../vendor/psr/log/Psr/Log/LogLevel) for a list of possibe values) and the
 * string to log.
 *
 * You can also send a third parameter being an array containing the placeholders to format the log,
 * this will override the format sent in the settings, you can add your custom placeholders this way.
 *
 * There are also 8 methods for loggin in a specific category:
 *  * `emergency`
 *  * `alert`
 *  * `critical`
 *  * `error`
 *  * `warning`
 *  * `notice`
 *  * `info`
 *  * `debug`
 *
 * All of them accepts as parameter the last 2 parameters of the `log` method.
 *
 * Example:
 *
 *     $Logger = new Logger(
 *         new \Alexya\FileSystem\Direcctory("/tmp/log/Alexya"),
 *         "{YEAR}-{MONTH}-{DAY}.log",
 *         "[{HOUR}:{MINUTE}] ({LEVEL}) {LOG}",
 *         [
 *             \Psr\Log\LogLevel::EMERGENCY,
 *             \Psr\Log\LogLevel::ALERT,
 *             \Psr\Log\LogLevel::CRITICAL,
 *             \Psr\Log\LogLevel::ERROR,
 *             \Psr\Log\LogLevel::WARNING,
 *             \Psr\Log\LogLevel::NOTICE,
 *             \Psr\Log\LogLevel::INFO,
 *             \Psr\Log\LogLevel::DEBUG
 *         ]
 *     );
 *
 *     $Logger->debug("test"); // [00:00] (debug) test
 *     $Logger->info("[{HOUR}:{MINUTE}] ({LEVEL}) {CUSTOM_PLACEHOLDER}", [
 *         "CUSTOM_PLACEHOLDER" => "test"
 *     ]); // [00:00] (debug) test
 *
 * @author Manulaiko <manulaiko@gmail.com>
 */
class File extends AbstractLogger
{
    /**
     * The directory containing log files
     *
     * @var \Alexya\FileSystem\Directory
     */
    private $_directory = null;

    /**
     * The format of the log file name
     *
     * @var string
     */
    private $_name_format = "{YEAR}-{MONTH}-{DAY}.log";

    /**
     * The format of each log entry
     *
     * @var string
     */
    private $_log_format = "[{HOUR}:{MINUTE}] ({LEVEL}) {LOG}";

    /**
     * What levels should the logger log
     *
     * @var array
     */
    private $_log_levels = [
                LogLevel::EMERGENCY,
                LogLevel::ALERT,
                LogLevel::CRITICAL,
                LogLevel::ERROR,
                LogLevel::WARNING,
                LogLevel::NOTICE,
                LogLevel::INFO,
                LogLevel::DEBUG
            ];

    /**
     * Constructor
     *
     * Example:
     *
     *     $Logger = new Logger(
     *         new \Alexya\FileSystem\Direcctory("/tmp/log/Alexya"),
     *         "{YEAR}-{MONTH}-{DAY}.log",
     *         "[{HOUR}:{MINUTE}] ({LEVEL}) {LOG}",
     *         [
     *             \Psr\Log\LogLevel::EMERGENCY,
     *             \Psr\Log\LogLevel::ALERT,
     *             \Psr\Log\LogLevel::CRITICAL,
     *             \Psr\Log\LogLevel::ERROR,
     *             \Psr\Log\LogLevel::WARNING,
     *             \Psr\Log\LogLevel::NOTICE,
     *             \Psr\Log\LogLevel::INFO,
     *             \Psr\Log\LogLevel::DEBUG
     *         ]
     *     );
     *
     * @param \Alexya\FileSystem\Directory $directory   The directory where the logs will be saved
     * @param string                       $name_format The format of the log file name
     * @param string                       $log_format  The format of each log entry
     * @param array                        $log_levels  What levels should the logger log
     */
    public function __construct(Directory $directory, string $name_format = "", string $log_format = "", array $log_levels = [])
    {
        $this->_directory   = $directory;

        if(!empty($name_format)) {
            $this->_name_format = $name_format;
        }
        if(!empty($log_format)) {
            $this->_log_format = $log_format;
        }
        if(!empty($log_levels)) {
            $this->_log_levels = $log_levels;
        }
    }

    /**
     * Performs the loggin
     *
     * If the `context` array isn't empty the logger will assume that
     * the `message` string contains placeholders and will override the
     * default log format:
     *
     *     // Default log format is "[{HOUR}:{MINUTE}] ({LEVEL}) {LOG}"
     *     $Logger->debug("test"); // [00:00] (debug) test
     *     $Logger->debug("{LEVEL}: {MESSAGE}", [
     *         "MESSAGE" => "test"
     *     ]); // debug: test
     *
     * If `level` isn't any of `\Psr\Log\LogLevel` constants will throw a `\Psr\Log\LogLevel\InvalidArgumentException`
     *
     * @param string $level   Log level
     * @param string $message Message to log
     * @param array  $context Custom placeholders
     *
     * @throws \Psr\Log\LogLevel\InvaildArgumentException If `level` isn't any of `\Psr\Log\LogLevel` constants
     */
    public function log(string $level, string $message, array $context = [])
    {
        // Check if $level is a valid log level
        try {
            if(!$this->_canLog($level)) {
                return;
            }
        } catch(InvalidArgumentException $e) {
            throw $e;
        }

        // Build the placeholders array for logging
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $caller    = $backtrace[1]
        if($caller["class"] == "Psr\Log\AbstractLogger") {
            $caller = $backtrace[2];
        }

        $placeholders = [
            "CALLER_CLASS"    => $caller["class"],
            "CALLER_FUNCTION" => $caller["function"],
            "CALLER_FILE"     => $caller["file"]
            "CALLER_TYPE"     => $caller["type"],
            "CALLER_LINE"     => $caller["line"],
            "LEVEL"           => $level,
            "LOG"             => $message
        ];

        // Format the message
        $log_message = $this->_parseContext($this->_log_format, $placeholders);
        if(!empty($context)) {
            unset($placeholders["LOG"]); // Unset it because $message already contains the format
            $log_message = $this->_parseContext($message, array_merge($context, $placeholders));
        }

        // Append it to the log file
        $file = $this->_getLogFile();
        $file->append($log_message."\n");
    }

    /**
     * Checks if the logger can log given level
     *
     * @param string $level Log level
     *
     * @return bool True if logger can log `level`, false if not
     *
     * @throws \Psr\Log\LogLevel\InvaildArgumentException If `level` isn't any of `\Psr\Log\LogLevel` constants
     */
    private function _canLog(string $level) : bool
    {
        $is_a_valid_level = false;

        if($level == LogLevel::EMERGENCY) {
            $is_a_valid_level = true;
        }
        if($level == LogLevel::ALERT) {
            $is_a_valid_level = true;
        }
        if($level == LogLevel::CRITICAL) {
            $is_a_valid_level = true;
        }
        if($level == LogLevel::ERROR) {
            $is_a_valid_level = true;
        }
        if($level == LogLevel::WARNING) {
            $is_a_valid_level = true;
        }
        if($level == LogLevel::NOTICE) {
            $is_a_valid_level = true;
        }
        if($level == LogLevel::INFO) {
            $is_a_valid_level = true;
        }
        if($level == LogLevel::DEBUG) {
            $is_a_valid_level = true;
        }

        if(!$is_a_valid_level) {
            throw new InvalidArgumentException("{$level} is not a valid log level!");
        }

        return in_array($level, $this->_log_levels);;
    }

    /**
     * Replaces all placeholders in `message` with the placeholders of `context`
     *
     * @param string $message Message to parse
     * @param array  $context Array with placeholders
     *
     * @return string Parsed message
     */
    private function _parseContext(string $message, array $context) : string
    {
        $context = array_merge($this->_getDefaultPlaceholders(), $context);

        // build a replacement array with braces around the context keys
        $replace = [];
        foreach($context as $key => $val) {
            // check that the value can be casted to string
            if(
                !is_array($val) &&
                (!is_object($val) || method_exists($val, '__toString'))
            ) {
                $replace['{'.$key.'}'] = $val;
            }
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * Returns an array with available placeholders
     *
     * @return array Array with available placeholders
     */
    private function _getDefaultPlaceholders() : array
    {
        $placeholders = [
            "YEAR"        => date("Y"),
            "MONTH"       => date("m"),
            "DAY"         => date("d"),
            "HOUR"        => date("H"),
            "MINUTE"      => date("i"),
            "SECOND"      => date("s"),
            "SERVER_NAME" => $_SERVER["SERVER_NAME"]
        ];

        return $placeholders;
    }

    /**
     * Finds (or creates) and returns the log file
     *
     * @return \Alexya\FileSystem\File The log file
     */
    private function _getLogFile() : File
    {
        // Format file name
        $name = $this->_parseContext($this->_name_format, $this->_getDefaultPlaceholders());

        return $this->_directory->getFile($name, true);
    }
}
