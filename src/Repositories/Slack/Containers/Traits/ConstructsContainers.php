<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015, 2016, 2017  Leon Jacobs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Containers\Traits;

use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException;

trait ConstructsContainers {

    /**
     * ConstructsContainers constructor.
     *
     * @param array|null $data
     *
     * @throws InvalidContainerDataException
     */
    public function __construct(array $data = null) {

        if (!is_null($data)) {

            foreach ($data as $key => $value) {

                if (!array_key_exists($key, $this->data))
                    throw new InvalidContainerDataException('Key ' . $key . ' is not valid for this container');

                $this->$key = $value;

            }

        }

    }

}
