<?php
namespace Alexya\Logger;

use \Alexya\Database\Connection;

use \Psr\Log\{
    AbstractLogger as PsrLogger,
    InvalidArgumentException,
    LogLevel
};

/**
 * Alexya's Abstract Logger.
 *
 * Base class for all PSR compatible loggers.
 *
 * The constructor accepts as parameter a string being the format that each
 * log message will have and an array with the log levels that the logger can log.
 *
 * All classes that extends this must implement the `_write` method that accepts as parameter
 * the string to log, this method will write the log message to whatever the child class wants.
 *
 * The method `log` performs the actual logging and accepts as parameter the log level
 * (see `\Psr\Log\LogLevel` for a list of possible values) and the string to log.
 *
 * There are also 8 methods for logging in a specific category:
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
 * ```php
 * class Logger extends AbstractLogger
 * {
 *     private function _write(string $message)
 *     {
 *         echo $message;
 *     }
 * }
 *
 * $logger = new Logger();
 * $logger->debug("test"); // test
 * $logger->debug("{LEVEL}: {MESSAGE}", [
 *     "MESSAGE" => "test"
 * ]); // debug: test
 * ```
 *
 * @author Manulaiko <manulaiko@gmail.com>
 */
abstract class AbstractLogger extends PsrLogger
{

    /**
     * The format of each log entry.
     *
     * @var string
     */
    protected $_log_format = "[{HOUR}:{MINUTE}] ({LEVEL}) {LOG}";

    /**
     * What levels should the logger log.
     *
     * @var array
     */
    protected $_log_levels = [
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
     * Constructor.
     *
     * Example:
     *
     * ```
     * $Logger = new Logger("[{HOUR}:{MINUTE}] ({LEVEL}) {LOG}", [
     *     \Psr\Log\LogLevel::EMERGENCY,
     *     \Psr\Log\LogLevel::ALERT,
     *     \Psr\Log\LogLevel::CRITICAL,
     *     \Psr\Log\LogLevel::ERROR,
     *     \Psr\Log\LogLevel::WARNING,
     *     \Psr\Log\LogLevel::NOTICE,
     *     \Psr\Log\LogLevel::INFO,
     *     \Psr\Log\LogLevel::DEBUG
     * ]);
     * ```
     *
     * @param string $log_format The format of each log entry.
     * @param array  $log_levels What levels should the logger log.
     */
    public function __construct(string $log_format = "", array $log_levels = [])
    {
        if(!empty($log_format)) {
            $this->_log_format = $log_format;
        }
        if(!empty($log_levels)) {
            $this->_log_levels = $log_levels;
        }
    }

    /**
     * Performs the logging.
     *
     * If the `context` array isn't empty the logger will assume that
     * the `message` string contains placeholders and will override the
     * default log format:
     *
     * ```php
     * // Default log format is "[{HOUR}:{MINUTE}] ({LEVEL}) {LOG}"
     * $Logger->debug("test"); // [00:00] (debug) test
     * $Logger->debug("{LEVEL}: {MESSAGE}", [
     *     "MESSAGE" => "test"
     * ]); // debug: test
     * ```
     *
     * If `level` isn't any of `\Psr\Log\LogLevel` constants will throw a `\Psr\Log\LogLevel\InvalidArgumentException`.
     *
     * @param string $level   Log level.
     * @param string $message Message to log.
     * @param array  $context Custom placeholders.
     *
     * @throws InvalidArgumentException If `level` isn't any of `\Psr\Log\LogLevel` constants.
     */
    public function log($level, $message, array $context = [])
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
        $caller    = $backtrace[1];
        if($caller["class"] === "Psr\\Log\\AbstractLogger") {
            $caller = $backtrace[2];
        }

        $placeholders = [
            "CALLER_CLASS"    => ($caller["class"] ?? ""),
            "CALLER_FUNCTION" => ($caller["function"] ?? ""),
            "CALLER_FILE"     => ($caller["file"] ?? ""),
            "CALLER_TYPE"     => ($caller["type"] ?? ""),
            "CALLER_LINE"     => ($caller["line"] ?? ""),
            "LEVEL"           => $level,
            "LOG"             => $message
        ];

        $placeholders = array_merge($placeholders, $this->_getDefaultPlaceholders(), $context);

        // Format the message
        $log_message = $this->_parseContext($this->_log_format, $placeholders);
        if(!empty($context)) {
            unset($placeholders["LOG"]); // Unset it because $message already contains the format
            $log_message = $this->_parseContext($message, $placeholders);
        }

        $this->_write($log_message, $placeholders);
    }

    /**
     * Checks if the logger can log given level.
     *
     * @param string $level Log level.
     *
     * @return bool True if logger can log `level`, false if not.
     *
     * @throws InvalidArgumentException If `level` isn't any of `\Psr\Log\LogLevel` constants.
     */
    protected function _canLog(string $level) : bool
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

        return in_array($level, $this->_log_levels);
    }

    /**
     * Replaces all placeholders in `message` with the placeholders of `context`.
     *
     * @param string $message Message to parse.
     * @param array  $context Array with placeholders.
     *
     * @return string Parsed message.
     */
    protected function _parseContext(string $message, array $context) : string
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
     * Returns an array with available placeholders.
     *
     * @return array Array with available placeholders.
     */
    protected function _getDefaultPlaceholders() : array
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
     * Writes the log message.
     *
     * All loggers must implement this method so they can decide
     * how log messages will be saved.
     *
     * @param string $message Message to log.
     * @param array  $context The default placeholders + user defined placeholders.
     */
    protected abstract function _write(string $message, array $context = []);
}
