<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 07/12/2017
 * Time: 22:42
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Access;


interface AccessInterface {

	public function can(string $method, string $uri, array $scopes) : bool;

}
