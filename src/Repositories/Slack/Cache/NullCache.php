<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 08/12/2017
 * Time: 21:21
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Cache;


use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackResponse;

class NullCache implements CacheInterface {

	public function set(string $uri, string $query, SlackResponse $data)
	{

	}

	public function get(string $uri, string $query = '')
	{
		return false;
	}

	public function forget(string $uri, string $query = '')
	{

	}

	public function has(string $uri, string $query = '') : bool
	{
		return false;
	}

}
