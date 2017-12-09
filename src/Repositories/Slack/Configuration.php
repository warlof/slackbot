<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 07/12/2017
 * Time: 22:35
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack;


use Warlof\Seat\Slackbot\Repositories\Slack\Cache\CacheInterface;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackConfiguration;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException;
use Warlof\Seat\Slackbot\Repositories\Slack\Log\LogInterface;

class Configuration {

	private static $instance;

	protected $logger;

	protected $cache;

	protected $configuration;

	private function __construct() {
		$this->configuration = new SlackConfiguration();
	}

	public static function getInstance() : Configuration
	{
		if (is_null(self::$instance))
			self::$instance = new self();

		return self::$instance;
	}

	public function getConfiguration()
	{
		return $this->configuration;
	}

	public function setConfiguration(SlackConfiguration $configuration)
	{
		if (!$configuration->valid())
			throw new InvalidConfigurationException('The configuration is empty or has invalid values.');

		$this->configuration = $configuration;
	}

	public function getLogger() : LogInterface
	{
		if (!$this->logger)
			$this->logger = new $this->configuration->logger;

		return $this->logger;
	}

	public function getCache() : CacheInterface
	{
		if (!$this->cache)
			$this->cache = new $this->configuration->cache;

		return $this->cache;
	}

	public function __get(string $name)
	{
		return $this->configuration->$name;
	}

	public function __set(string $name, string $value)
	{
		return $this->configuration->$name = $value;
	}
}
