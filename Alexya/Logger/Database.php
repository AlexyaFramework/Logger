<?php
namespace Alexya\Logger;

use \Alexya\Database\Connection;
use Alexya\Database\QueryBuilder;

/**
 * Alexya's Database Logger.
 *
 * Implements a PSR compatible database logger.
 *
 * The constructor accepts as parameter the following parameters:
 *
 *  * The `\Alexya\Database\Connection` object that will be used for interacting with the database.
 *  * A string being the table name.
 *  * An associative array containing the columns and the values to insert, you can insert the following placeholders:
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
 *    of available values in the class `\Psr\Log\LogLevel`
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
 * $Logger = new Logger(
 *     $Database,
 *     "logs",
 *     [
 *         "date"    => "{YEAR}-{MONTH}-{DAY} {HOUR}:{MINUTE}:{SECOND}",
 *         "caller"  => "{CALLER_CLASS}{CALLER_TYPE}{CALLER_FUNCTION} ({CALLER_FILE}:{CALLER_LINE})",
 *         "level"   => "{LEVEL}",
 *         "message" => "{LOG}"
 *     ],
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
 * $Logger->debug("test"); // INSERT INTO `logs` (`date`, `caller`, `level`, `message`) VALUES ('0000-00-00 00:00:00', '', 'debug', 'test');
 * $Logger->info("test", [
 *     "date"    => "{HOUR}:{MINUTE}:{SECOND}",
 *     "caller"  => "{CALLER_CLASS}{CALLER_TYPE}{CALLER_FUNCTION} ({CALLER_FILE}:{CALLER_LINE})",
 *     "level"   => "{LEVEL}",
 *     "message" => "{LOG}"
 * ]); // INSERT INTO `logs` (`date`, `caller`, `level`, `message`) VALUES ('00:00:00', '', 'debug', 'test');
 * ```
 *
 * @author Manulaiko <manulaiko@gmail.com>
 */
class Database extends AbstractLogger
{
    /**
     * The database object.
     *
     * @var Connection
     */
    private $_database = null;

    /**
     * The table where logs should be saved.
     *
     * @var string
     */
    private $_table = "logs";

    /**
     * The array containing the columns and the values to insert.
     *
     * @var string
     */
    private $_columns = [
            "date"    => "{YEAR}-{MONTH}-{DAY}",
            "caller"  => "{CALLER_CLASS}{CALLER_TYPE}{CALLER_FUNCTION} ({CALLER_FILE}:{CALLER_LINE})",
            "level"   => "{LEVEL}",
            "message" => "{LOG}"
    ];

    /**
     * Constructor.
     *
     * Example:
     *
     * ```php
     * $Logger = new Logger(
     *     $Database,
     *     "logs",
     *     [
     *         "date"    => "{YEAR}-{MONTH}-{DAY} {HOUR}:{MINUTE}:{SECOND}",
     *         "caller"  => "{CALLER_CLASS}{CALLER_TYPE}{CALLER_FUNCTION} ({CALLER_FILE}:{CALLER_LINE})",
     *         "level"   => "{LEVEL}",
     *         "message" => "{LOG}"
     *     ],
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
     * @param Connection $database   The database connection object.
     * @param string     $table_name The table where logs should be saved.
     * @param array      $columns    The array containing the rows and the values to insert.
     * @param string     $log_format The format of each log entry.
     * @param array      $log_levels What levels should the logger log.
     */
    public function __construct(
        Connection $database,
        string     $table_name = "",
        array      $columns    = [],
        string     $log_format = "",
        array      $log_levels = []
    ) {
        $this->_database = $database;

        if(!empty($table_name)) {
            $this->_table = $table_name;
        }
        if(!empty($columns)) {
            $this->_columns = $columns;
        }

        parent::__construct($log_format, $log_levels);
    }

    /**
     * Writes the log message to the database table.
     *
     * @param string $message Message to log.
     * @param array  $context Custom placeholders.
     */
    protected function _write(string $message, array $context = [])
    {
        $query = new QueryBuilder($this->_database);

        // Insert a new row into database
        $query = $query->insert($this->_table)
                       ->values($this->_columns)
                       ->getQuery();

        $result = $this->_database->insert($query);

        if(!is_numeric($result)) {
            // Something went wrong, idk what to do here...
        }
    }
}
