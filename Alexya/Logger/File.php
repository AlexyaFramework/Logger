<?php
namespace Alexya\Logger;

use \Alexya\FileSystem\{
    Directory,
    File as FileObject
};

/**
 * Alexya's Logger.
 *
 * Implements a PSR compatible file logger.
 *
 * The constructor accepts as parameter the following parameters:
 *
 *  * The `\Alexya\FileSystem\File]` object that will represent the path where the log files will be saved.
 *  * A string being the format of each log file name, you can add the following placeholders:
 *      * `{YEAR}`, current year.
 *      * `{MONTH}`, current month.
 *      * `{DAY}`, current day.
 *      * `{HOUR}`, current hour.
 *      * `{MINUTE}`, current minute.
 *      * `{SERVER_NAME}`, server's name (`localhost`, `test.com`...).
 *  * A string being the format that each log entry will have, you can add the following placeholders:
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
 *      * `{LOG}`, the string to log.
 *  * An array containing the elements that will be logged, you can get a full list
 *    of available values in the class [\Psr\Log\LogLevel](../vendor/psr/log/Psr/Log/LogLevel)
 *
 * The method `log` performs the actual logging and accepts as parameter the log level
 * (see `\Psr\Log\LogLevel` for a list of possible values) and the string to log.
 *
 * You can also send a third parameter being an array containing the placeholders to format the log,
 * this will override the format sent in the settings, you can add your custom placeholders this way.
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
 * $Logger = new \Alexya\Logger\File(
 *     new \Alexya\FileSystem\Directory("/tmp/log/Alexya"),
 *     "{YEAR}-{MONTH}-{DAY}.log",
 *     "[{HOUR}:{MINUTE}] ({LEVEL}) {LOG}",
 *     [
 *         \Psr\Log\LogLevel::EMERGENCY,
 *         \Psr\Log\LogLevel::ALERT,
 *         \Psr\Log\LogLevel::CRITICAL,
 *         \Psr\Log\LogLevel::ERROR,
 *         \Psr\Log\LogLevel::WARNING,
 *         \Psr\Log\LogLevel::NOTICE,
 *         \Psr\Log\LogLevel::INFO,
 *         \Psr\Log\LogLevel::DEBUG
 *     ]
 * );
 *
 * $Logger->debug("test"); // [00:00] (debug) test
 * $Logger->info("[{HOUR}:{MINUTE}] ({LEVEL}) {CUSTOM_PLACEHOLDER}", [
 *     "CUSTOM_PLACEHOLDER" => "test"
 * ]); // [00:00] (debug) test
 * ```
 *
 * @author Manulaiko <manulaiko@gmail.com>
 */
class File extends AbstractLogger
{
    /**
     * The directory containing log files.
     *
     * @var Directory
     */
    private $_directory = null;

    /**
     * The format of the log file name.
     *
     * @var string
     */
    private $_name_format = "{YEAR}-{MONTH}-{DAY}.log";

    /**
     * Constructor.
     *
     * Example:
     *
     * ```php
     * $Logger = new \Alexya\Logger\File(
     *     new \Alexya\FileSystem\Directory("/tmp/log/Alexya"),
     *     "{YEAR}-{MONTH}-{DAY}.log",
     *     "[{HOUR}:{MINUTE}] ({LEVEL}) {LOG}",
     *     [
     *         \Psr\Log\LogLevel::EMERGENCY,
     *         \Psr\Log\LogLevel::ALERT,
     *         \Psr\Log\LogLevel::CRITICAL,
     *         \Psr\Log\LogLevel::ERROR,
     *         \Psr\Log\LogLevel::WARNING,
     *         \Psr\Log\LogLevel::NOTICE,
     *         \Psr\Log\LogLevel::INFO,
     *         \Psr\Log\LogLevel::DEBUG
     *     ]
     * );
     * ```
     *
     * @param Directory $directory   The directory where the logs will be saved.
     * @param string    $name_format The format of the log file name.
     * @param string    $log_format  The format of each log entry.
     * @param array     $log_levels  What levels should the logger log.
     */
    public function __construct(
        Directory $directory,
        string    $name_format = "",
        string    $log_format  = "",
        array     $log_levels  = []
    ) {
        $this->_directory = $directory;

        if(!empty($name_format)) {
            $this->_name_format = $name_format;
        }

        parent::__construct($log_format, $log_levels);
    }

    /**
     * Appends the log message to the log file.
     *
     * @param string $message Message to log.
     * @param array  $context Custom placeholders.
     */
    protected function _write(string $message, array $context = [])
    {
        // Append it to the log file
        $file = $this->_getLogFile();
        $file->append($message."\n");
    }

    /**
     * Finds (or creates) and returns the log file.
     *
     * @return FileObject The log file.
     */
    private function _getLogFile() : FileObject
    {
        // Format file name
        $name = $this->_parseContext($this->_name_format, $this->_getDefaultPlaceholders());

        return $this->_directory->getFile($name, Directory::GET_FILE_NOT_EXISTS_CREATE);
    }
}
