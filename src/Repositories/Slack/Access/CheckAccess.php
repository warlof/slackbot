<?php
/**
 * This file is part of slackbot and provide user synchronization between both SeAT and a Slack Team
 *
 * Copyright (C) 2016, 2017, 2018, 2019  Loïc Leuilliot <loic.leuilliot@gmail.com>
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

namespace Warlof\Seat\Slackbot\Repositories\Slack\Access;

use Warlof\Seat\Slackbot\Repositories\Slack\Configuration;

class CheckAccess implements AccessInterface {

    protected $scope_map = [
        'get' => [
            '/auth.test'             => [],
            '/users.list'            => ['users:read', 'read'],
            '/users.info'            => ['users:read', 'read'],
            '/conversations.info'    => ['channels:read', 'groups:read', 'im:read', 'mpim:read', 'read'],
            '/conversations.list'    => ['channels:read', 'groups:read', 'im:read', 'mpim:read', 'read'],
            '/conversations.members' => ['channels:read', 'groups:read', 'im:read', 'mpim:read', 'read'],
            '/users.lookupByEmail'   => ['users:read.email'],
        ],
        'post' => [
            '/conversations.invite'  => ['channels:write', 'groups:write', 'im:write', 'mpim:write', 'post'],
            '/conversations.kick'    => ['channels:write', 'groups:write', 'im:write', 'mpim:write', 'post'],
            '/conversations.join'    => ['channels:write', 'post'],
            '/users.lookupByEmail'   => ['users:read.email'],
        ],
    ];

    /**
     * @param string $method
     * @param string $uri
     * @param array $scopes
     * @return bool
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     */
    public function can(string $method, string $uri, array $scopes) : bool
    {
        if (!array_key_exists($uri, $this->scope_map[$method])) {
            Configuration::getInstance()->getLogger()
                ->warning('An unknown URI was called. Allowing ' . $uri);

            return true;
        }

        $required_scope = $this->scope_map[$method][$uri];

        if (array_diff($scopes, $required_scope) == $required_scope)
            return false;

        return true;
    }
}
