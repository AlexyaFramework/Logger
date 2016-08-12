# Logger
Alexya's logger componets

## Contents
- [Abstract logger](#abstract_logger)
- [File logger](#file_logger)
- [Database logger](#database_logger)

<a name="abstract_logger"></a>
## Abstract logger
`\Alexya\Logger\AbstractLogger` is the base class for all different loggers available. It
is the class that checks which messages can be logged and formats them.

Its constructor accepts the following parameters:

 * A string being the format that each log message will have.
 * An array with the log levels that the logger can log.

The string parameter can contain placeholders for formatting the message, this are the available placeholders:

 * `{YEAR}`, current year.
 * `{MONTH}`, current month.
 * `{DAY}`, current day.
 * `{HOUR}`, current hour.
 * `{MINUTE}`, current minute.
 * `{SECOND}`, current second.
 * `{SERVER_NAME}`, server's name (`localhost`, `test.com`...).
 * `{CALLING_FUNCTION}`, the function that called the logger.
 * `{CALLING_FILE}`, the file that called the logger.
 * `{CALLING_LINE}`, the line that called the logger.
 * `{CALLING_CLASS}`, the class that called the logger.
 * `{CALLING_TYPE}`, `->` if the logger was called by an object, `::` if it was called statically.
 * `{LEVEL}`, the level on which the log has been called.
 * `{LOG}`, the string to log.

If the string is empty the logger will use `[{HOUR}:{MINUTE}] ({LEVEL}) {LOG}` as format.

The array parameter contains the levels that the logger can log, if it's empty it will log all levels.
This are supported levels:

 * `emergency`
 * `alert`
 * `critical`
 * `error`
 * `warning`
 * `notice`
 * `info`
 * `debug`

The class `\Psr\Log\LogLevel` contains the constants definitions for all levels.

The `log` method will first see if the log message can be logged and then format it (if it can be logged),
it accepts three parameters:

 * A string being the level on which the message should be logged.
 * A string being the message to log (can contain placeholders).
 * An array containing custom placeholders.

If the level isn't any of the costants defined in `\Psr\Log\LogLevel` it will throw an exception of type `\Psr\Log\InvalidArgumentException`.

All classes that extends this class must implement the method `_write` which will write the log message
to wherever the child class wants. It accepts as parameter the formatted message and an the placeholders array:

```php
<?php
namespace Test;

use \Alexya\Logger\AbstractLogger;

/**
 * Simple logger that echoes each log entry
 */
class Logger extends AbstractLogger
{
    protected function _write($message, $placeholders)
    {
        echo $message;
    }
}
```

<a name="file_logger"></a>
## File logger
The class `\Alexya\Logger\File` outputs each log message to a file.

The constructor accepts four parameters:

 * The `\Alexya\FileSystem\File` object that will represent the path where the log files will be saved.
 * A string being the format of each log file name, you can add the followin placeholders:
 * A string being the format that each log entry will have.
 * An array containing the elements that will be logged, you can get a full list
   of available values in the class `\Psr\Log\LogLevel`.

The file name format can have the followin placeholders:

 * `{YEAR}`, current year.
 * `{MONTH}`, current month.
 * `{DAY}`, current day.
 * `{HOUR}`, current hour.
 * `{MINUTE}`, current minute.
 * `{SECOND}`, current second.
 * `{SERVER_NAME}`, server's name (`localhost`, `test.com`...).

If the file doesn't exist in the moment the message is going to be logged it will be created.

```php
<?php
$Logger = new \Alexya\Logger\File(
    new \Alexya\FileSystem\Direcctory("/tmp/log/Alexya"),
    "{YEAR}-{MONTH}-{DAY}.log",
    "[{HOUR}:{MINUTE}] ({LEVEL}) {LOG}",
    [
        \Psr\Log\LogLevel::EMERGENCY,
        \Psr\Log\LogLevel::ALERT,
        \Psr\Log\LogLevel::CRITICAL,
        \Psr\Log\LogLevel::ERROR,
        \Psr\Log\LogLevel::WARNING,
        \Psr\Log\LogLevel::NOTICE,
        \Psr\Log\LogLevel::INFO,
        \Psr\Log\LogLevel::DEBUG
    ]
);

$Logger->debug("test"); // [00:00] (debug) test
$Logger->info("[{HOUR}:{MINUTE}] ({LEVEL}) {CUSTOM_PLACEHOLDER}", [
    "CUSTOM_PLACEHOLDER" => "test"
]); // [00:00] (debug) test
```

<a name="database_logger"></a>
## Database logger
The class `\Alexya\Logger\Database` stores each log message in a database table.

The constructor acccepts five parameters:

 * The `\Alexya\Database\Connection` object that will be used for interacting with the database.
 * A string being the table name.
 * An associative array containing the columns and the values to insert.
 * A string being the format that each log entry will have.
 * An array containing the elements that will be logged, you can get a full list
   of available values in the class `\Psr\Log\LogLevel`.

Both, the columns array and the format string can accept the followin placeholders:

 * `{YEAR}`, current year.
 * `{MONTH}`, current month.
 * `{DAY}`, current day.
 * `{HOUR}`, current hour.
 * `{MINUTE}`, current minute.
 * `{SECOND}`, current second.
 * `{SERVER_NAME}`, server's name (`localhost`, `test.com`...).
 * `{CALLING_FUNCTION}`, the function that called the logger.
 * `{CALLING_FILE}`, the file that called the logger.
 * `{CALLING_LINE}`, the line that called the logger.
 * `{CALLING_CLASS}`, the class that called the logger.
 * `{CALLING_TYPE}`, `->` if the logger was called by an object, `::` if it was called statically.
 * `{LEVEL}`, the level on which the log has been called.
 * `{LOG}`, the string to log.

```php
<?php
$Logger = new \Alexya\Logger\Database(
    $Database,
    "logs",
    [
        "date"    => "{YEAR}-{MONTH}-{DAY} {HOUR}:{MINUTE}:{SECOND}",
        "caller"  => "{CALLER_CLASS}{CALLER_TYPE}{CALLER_FUNCTION} ({CALLER_FILE}:{CALLER_LINE})",
        "level"   => "{LEVEL}",
        "message" => "{LOG}"
    ],
    [
        \Psr\Log\LogLevel::EMERGENCY,
        \Psr\Log\LogLevel::ALERT,
        \Psr\Log\LogLevel::CRITICAL,
        \Psr\Log\LogLevel::ERROR,
        \Psr\Log\LogLevel::WARNING,
        \Psr\Log\LogLevel::NOTICE,
        \Psr\Log\LogLevel::INFO,
        \Psr\Log\LogLevel::DEBUG
    ]
);

$Logger->debug("test"); // INSERT INTO `logs` (`date`, `caller`, `level`, `message`) VALUES ('0000-00-00 00:00:00', '', 'debug', 'test');
$Logger->info("test", [
    "date"    => "{HOUR}:{MINUTE}:{SECOND}",
    "caller"  => "{CALLER_CLASS}{CALLER_TYPE}{CALLER_FUNCTION} ({CALLER_FILE}:{CALLER_LINE})",
    "level"   => "{LEVEL}",
    "message" => "{LOG}"
]); // INSERT INTO `logs` (`date`, `caller`, `level`, `message`) VALUES ('00:00:00', '', 'debug', 'test');
```
