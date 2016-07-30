<?php
namespace Alexya\Logger;

use \Alexya\Database\Connection;

/**
 * Alexya's Database Logger.
 *
 * Implements a PSR compatible database logger.
 *
 * The constructor accepts as parameter the following parameters:
 *
 *  * The [\Alexya\Database\Connection](../../vendor/alexya-framework/filesystem/Alexya/Database/Connection.php)
 *    object that will be used for interacting with the database.
 *  * A string being the table name.
 *  * An associative array containing the rows and the values to insert, you can insert the followin placeholders:
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
 *    of available values in the class [\Psr\Log\LogLevel](../../vendor/psr/log/Psr/Log/LogLevel)
 *
 * The method `log` performs the actual loggin and accepts as parameter the log level
 * (see [\Psr\Log\LogLevel](../../vendor/psr/log/Psr/Log/LogLevel) for a list of possibe values) and the
 * string to log.
 *
 * You can also send a third parameter being an array containing the rows and values to insert, this will
 * override the array sent to the constructor.
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
 *         $Database,
 *         "logs",
 *         [
 *             "date"    => "{YEAR}-{MONTH}-{DAY} {HOUR}:{MINUTE}:{SECOND}",
 *             "caller"  => "{CALLER_CLASS}{CALLER_TYPE}{CALLER_FUNCTION} ({CALLER_FILE}:{CALLER_LINE})",
 *             "level"   => "{LEVEL}",
 *             "message" => "{LOG}"
 *         ],
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
 *     $Logger->debug("test"); // INSERT INTO `logs` (`date`, `caller`, `level`, `message`) VALUES ('0000-00-00 00:00:00', '', 'debug', 'test');
 *     $Logger->info("test", [
 *         "date"    => "{HOUR}:{MINUTE}:{SECOND}",
 *         "caller"  => "{CALLER_CLASS}{CALLER_TYPE}{CALLER_FUNCTION} ({CALLER_FILE}:{CALLER_LINE})",
 *         "level"   => "{LEVEL}",
 *         "message" => "{LOG}"
 *     ]); // INSERT INTO `logs` (`date`, `caller`, `level`, `message`) VALUES ('00:00:00', '', 'debug', 'test');
 *
 * @author Manulaiko <manulaiko@gmail.com>
 */
class Database extends AbstractLogger
{
    /**
     * The database object.
     *
     * @var \Alexya\Database\Connection
     */
    private $_database = null;

    /**
     * The table where logs should be saved.
     *
     * @var string
     */
    private $_table_name = "logs";

    /**
     * The array containing the rows and the values to insert.
     *
     * @var string
     */
    private $_rows = [
                "date"    => "{YEAR}-{MONTH}-{DAY}",
                "caller"  => "{CALLER_CLASS}{CALLER_TYPE}{CALLER_FUNCTION} ({CALLER_FILE}:{CALLER_LINE})",
                "level"   => "{LEVEL}",
                "message" => "{LOG}"
            ];
    /**
     * Constructor
     *
     * Example:
     *
     *     $Logger = new \Alexya\Logger\Database(
     *         $Database,
     *         "logs",
     *         [
     *             "date"    => "{YEAR}-{MONTH}-{DAY} {HOUR}:{MINUTE}:{SECOND}",
     *             "caller"  => "{CALLER_CLASS}{CALLER_TYPE}{CALLER_FUNCTION} ({CALLER_FILE}:{CALLER_LINE})",
     *             "level"   => "{LEVEL}",
     *             "message" => "{LOG}"
     *         ],
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
     * @param \Alexya\Database\Connection $database   The database connection object.
     * @param string                      $table_name The table where logs should be saved.
     * @param array                       $rows       The array containing the rows and the values to insert.
     * @param string                      $log_format The format of each log entry
     * @param array                       $log_levels What levels should the logger log
     */
    public function __construct(
        Connection $database,
        string     $table_name = "",
        array      $rows       = [],
        string     $log_format = "",
        array      $log_levels = []
    ) {
        $this->_database   = $database;

        if(!empty($table_name)) {
            $this->_table_name = $table_name;
        }
        if(!empty($rows)) {
            $this->_rows = $rows;
        }

        parent::__construct($log_format, $log_levels);
    }

    /**
     * Writes the log message to the database table
     *
     * @param string $message Message to log
     * @param array  $context Custom placeholders
     */
    private function _write(string $message, array $context)
    {
        $placeholders = [
            "CALLER_CLASS"    => $caller["class"],
            "CALLER_FUNCTION" => $caller["function"],
            "CALLER_FILE"     => $caller["file"]
            "CALLER_TYPE"     => $caller["type"],
            "CALLER_LINE"     => $caller["line"],
            "LEVEL"           => $level,
            "LOG"             => $message
        ];

        foreach($this->_rows as $key => $value) {
            $this->_rows[$key] = $this->_parseContext($value, array_merge($placeholders, $this->_getDefaultPlaceholders()));
        }

        // Append it to the log file
        $query = $Database->insert($this->_table)
                          ->values($this->_rows)
                          ->execute();
        if(!is_numeric($query)) {
            // Something went wrong, idk what to do here...
        }
    }
}
