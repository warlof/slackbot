<?php
/**
 * This file is part of seat-slackbot and provide user synchronization between both SeAT and a Slack Team
 *
 * Copyright (C) 2016, 2017, 2018  Loïc Leuilliot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Log;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Warlof\Seat\Slackbot\Repositories\Slack\Configuration;

class FileLogger implements LogInterface {

    protected $logger;

    /**
     * FileLogger constructor.
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     */
    public function __construct() {

        $configuration = Configuration::getInstance();

        $formatter = new LineFormatter('[%datetime%] %channel%.%level_name%: %message%' . PHP_EOL);

        $stream = new RotatingFileHandler(
            $configuration->logfile_location,
            0,
            $configuration->logger_level
        );
        $stream->setFormatter($formatter);

        $this->logger = new Logger('slack');
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

    public function warning(string $message, array $context = []) {
        $this->logger->addWarning($message, $context);
    }

    public function error(string $message, array $context = []) {
        $this->logger->addError($message, $context);
    }

}
