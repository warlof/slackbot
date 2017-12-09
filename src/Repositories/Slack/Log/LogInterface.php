<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 08/12/2017
 * Time: 22:25
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Log;


interface LogInterface {

	public function log(string $message);

	public function debug(string $message);

	public function warning(string $message);

	public function error(string $message);

}
