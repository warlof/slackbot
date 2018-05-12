<?php
/**
 * This file is part of slackbot and provide user synchronization between both SeAT and a Slack Team
 *
 * Copyright (C) 2016, 2017, 2018  Loïc Leuilliot <loic.leuilliot@gmail.com>
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

namespace Warlof\Seat\Slackbot\Repositories\Slack\Containers;

use ArrayObject;
use Carbon\Carbon;
use stdClass;

class SlackResponse extends ArrayObject
{
    protected $expires_at;

    protected $response_code;

    protected $error_message;

    public function __construct(stdClass $data, string $expires, int $response_code)
    {
        $this->expires_at    = strlen($expires) > 2 ? $expires : 'now';
        $this->response_code = $response_code;

        // force cache on entries
        if (carbon($this->expires_at)->lte(carbon()->now(carbon($this->expires_at)->timezoneName)))
            $this->expires_at = carbon('now')->addHour();

        if (property_exists($data, 'error'))
            $this->error_message = $data->error;

        if (property_exists($data, 'errors'))
            $this->error_message = print_r($data->errors, true);

        if (property_exists($data, 'error_description'))
            $this->error_message .= $data->error_description;

        parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
    }

    public function expired() : bool
    {
        if ($this->expires()->lte(
            carbon()->now($this->expires()->timezoneName)
        ))
            return true;

        return false;
    }

    public function expires() : Carbon
    {
        return carbon($this->expires_at);
    }

    public function error()
    {
        return $this->error_message;
    }

    public function getErrorCode() : int
    {
        return $this->response_code;
    }

}
