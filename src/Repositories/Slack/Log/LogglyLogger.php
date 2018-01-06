<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 06/01/2018
 * Time: 22:36
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Log;


use Monolog\Formatter\LineFormatter;
use Monolog\Handler\LogglyHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Warlof\Seat\Slackbot\Repositories\Slack\Configuration;

class LogglyLogger implements LogInterface {

    protected $logger;

    public function __construct()
    {
        $configuration = Configuration::getInstance();

        $formatter = new LineFormatter('[%datetime%] %channel%.%level_name%: %message%' . PHP_EOL . '%context%' . PHP_EOL);

        $stream = new RotatingFileHandler(
            $configuration->logfile_location,
            0,
            $configuration->logger_level
        );
        $stream->setFormatter($formatter);

        $loggly = new LogglyHandler(config('slackbot.config.loggly.key'), $configuration->logger_level);
        $loggly->setTag(config('slackbot.config.loggly.tag'));

        $this->logger = new Logger('slack');
        $this->logger->pushHandler($loggly);
        $this->logger->pushHandler($stream);
    }

    public function log(string $message, array $context = [])
    {
        $this->logger->addInfo($message, $context);
    }

    public function debug(string $message, array $context = [])
    {
        $this->logger->addDebug($message, $context);
    }

    public function warning(string $message, array $context = [])
    {
        $this->logger->addWarning($message, $context);
    }

    public function error(string $message, array $context = [])
    {
        $this->logger->addError($message, $context);
    }
}
