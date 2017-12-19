<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 08/12/2017
 * Time: 22:26
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Log;


use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Warlof\Seat\Slackbot\Repositories\Slack\Configuration;

class FileLogger implements LogInterface {

    protected $logger;

    public function __construct() {

        $configuration = Configuration::getInstance();

        $formatter = new LineFormatter('[%datetime%] %channel%.%level_name%: %message%' . PHP_EOL);

        $stream = new StreamHandler(
            $configuration->logfile_location,
            $configuration->logger_level
        );
        $stream->setFormatter($formatter);

        $this->logger = new Logger('slack');
        $this->logger->pushHandler($stream);
    }

    public function log(string $message)
    {
        $this->logger->addInfo($message);
    }

    public function debug(string $message)
    {
        $this->logger->addDebug($message);
    }

    public function warning(string $message) {
        $this->logger->addWarning($message);
    }

    public function error(string $message) {
        $this->logger->addError($message);
    }

}
