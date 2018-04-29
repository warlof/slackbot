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
