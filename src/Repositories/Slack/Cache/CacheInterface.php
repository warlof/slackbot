<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 07/12/2017
 * Time: 22:46
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Cache;


use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackResponse;

interface CacheInterface {

	public function set(string $uri, string $query, SlackResponse $data);

	public function get(string $uri, string $query = '');

	public function forget(string $uri, string $query = '');

	public function has(string $uri, string $query = '') : bool;

}
