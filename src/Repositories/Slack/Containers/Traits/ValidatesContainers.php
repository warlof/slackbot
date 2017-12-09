<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 08/12/2017
 * Time: 22:43
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Containers\Traits;


trait ValidatesContainers {

	public function valid() : bool
	{
		return !in_array(null, $this->data, true);
	}

}
