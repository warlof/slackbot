<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 08/12/2017
 * Time: 21:24
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Containers;


use ArrayAccess;

abstract class AbstractArrayAccess implements ArrayAccess {

	protected $data;

	public function offsetExists($offset) : bool
	{
		return array_key_exists($offset, $this->data);
	}

	public function offsetGet($offset)
	{
		return $this->data[$offset];
	}

	public function offsetSet($offset, $value)
	{
		$this->data[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->data[$offset]);
	}

	public function __get($name)
	{
		return $this[$name];
	}

	public function __set($name, $value)
	{
		$this[$name] = $value;
	}

}
