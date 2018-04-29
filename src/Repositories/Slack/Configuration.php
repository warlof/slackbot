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

namespace Warlof\Seat\Slackbot\Repositories\Slack;

use Warlof\Seat\Slackbot\Repositories\Slack\Cache\CacheInterface;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackConfiguration;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException;
use Warlof\Seat\Slackbot\Repositories\Slack\Log\LogInterface;

class Configuration {

    /**
     * @var Configuration
     */
    private static $instance;

    /**
     * @var LogInterface
     */
    protected $logger;

    /**
     * @var
     */
    protected $cache;

    /**
     * @var SlackConfiguration
     */
    protected $configuration;

    /**
     * Configuration constructor.
     * @throws Exceptions\InvalidContainerDataException
     */
    private function __construct() {
        $this->configuration = new SlackConfiguration();
    }

    /**
     * @return Configuration
     * @throws Exceptions\InvalidContainerDataException
     */
    public static function getInstance() : Configuration
    {
        if (is_null(self::$instance))
            self::$instance = new self();

        return self::$instance;
    }

    /**
     * @return SlackConfiguration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param SlackConfiguration $configuration
     *
     * @throws InvalidConfigurationException
     */
    public function setConfiguration(SlackConfiguration $configuration)
    {
        if (!$configuration->valid())
            throw new InvalidConfigurationException('The configuration is empty or has invalid values.');

        $this->configuration = $configuration;
    }

    /**
     * @return LogInterface
     */
    public function getLogger() : LogInterface
    {
        if (!$this->logger)
            $this->logger = new $this->configuration->logger;

        return $this->logger;
    }

    /**
     * @return CacheInterface
     */
    public function getCache() : CacheInterface
    {
        if (!$this->cache)
            $this->cache = new $this->configuration->cache;

        return $this->cache;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->configuration->$name;
    }

    /**
     * @param string $name
     * @param string $value
     * @return string
     */
    public function __set(string $name, string $value)
    {
        return $this->configuration->$name = $value;
    }
}
