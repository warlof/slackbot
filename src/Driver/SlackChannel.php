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

namespace Warlof\Seat\Connector\Drivers\Slack\Driver;

use Warlof\Seat\Connector\Drivers\ISet;
use Warlof\Seat\Connector\Drivers\IUser;

/**
 * Class SlackChannel.
 *
 * @package Warlof\Seat\Connector\Drivers\Slack\Driver
 */
class SlackChannel implements ISet
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \Warlof\Seat\Connector\Drivers\IUser[]
     */
    private $members;

    /**
     * @var bool
     */
    private $private;

    /**
     * SlackChannel constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->members = collect();
        $this->hydrate($attributes);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return \Warlof\Seat\Connector\Drivers\IUser[]
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function getMembers(): array
    {
        if ($this->members->isEmpty())
            $this->seedMembers();

        return $this->members->toArray();
    }

    /**
     * @param \Warlof\Seat\Connector\Drivers\IUser $user
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function addMember(IUser $user)
    {
        if (in_array($user, $this->getMembers()))
            return;

        SlackClient::getInstance()->sendCall('POST', '/conversations.invite', [
            'channel' => $this->id,
            'users'   => implode(',', [$user->getClientId()]),
        ]);

        $this->members->put($user->getClientId(), $user);
    }

    /**
     * @param \Warlof\Seat\Connector\Drivers\IUser $user
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function removeMember(IUser $user)
    {
        if (! in_array($user, $this->getMembers()))
            return;

        SlackClient::getInstance()->sendCall('POST', '/conversations.kick', [
            'channel' => $this->id,
            'user'    => $user->getClientId(),
        ]);

        $this->members->pull($user->getClientId());
    }

    /**
     * @param array $attributes
     * @return \Warlof\Seat\Connector\Drivers\Slack\Driver\SlackChannel
     */
    public function hydrate(array $attributes = []): SlackChannel
    {
        $this->id      = $attributes['id'];
        $this->name    = $attributes['name'];
        $this->private = $attributes['is_group'];

        return $this;
    }

    /**
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    private function seedMembers()
    {
        $body = SlackClient::getInstance()->sendCall('GET', '/conversations.members', [
            'channel' => $this->id,
        ]);

        foreach ($body->members as $member_id) {
            $entity = SlackClient::getInstance()->getUser($member_id);

            $this->members->put($entity->getClientId(), $entity);
        }
    }
}
