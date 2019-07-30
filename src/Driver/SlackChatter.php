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

use Illuminate\Support\Str;
use Warlof\Seat\Connector\Drivers\ISet;
use Warlof\Seat\Connector\Drivers\IUser;

/**
 * Class SlackChatter.
 *
 * @package Warlof\Seat\Connector\Drivers\Slack
 */
class SlackChatter implements IUser
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
     * @var string
     */
    private $email;

    /**
     * @var \Warlof\Seat\Connector\Drivers\ISet[]
     */
    private $channels;

    /**
     * SlackChatter constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->channels = collect();
        $this->hydrate($attributes);
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function setName(string $name)
    {
        $nickname = Str::limit($name, 32, '');

        SlackClient::getInstance()->sendCall('POST', '/users.profile.set', [
            'user' => $this->id,
            'name' => 'display_name',
            'value' => $nickname,
        ]);

        $this->name = $nickname;
    }

    /**
     * @return \Warlof\Seat\Connector\Drivers\ISet[]
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function getSets(): array
    {
        if ($this->channels->isEmpty()) {
            $channels = SlackClient::getInstance()->getSets();

            $this->channels = collect(array_filter($channels, function ($channel) {
                return in_array($this, $channel->getMembers());
            }));
        }

        return $this->channels->toArray();
    }

    /**
     * @param array $attributes
     * @return \Warlof\Seat\Connector\Drivers\Slack\Driver\SlackChatter
     */
    public function hydrate(array $attributes = []): SlackChatter
    {
        $this->id    = $attributes['id'];
        $this->email = $attributes['email'];
        $this->name  = $attributes['name'];

        return $this;
    }

    /**
     * @param \Warlof\Seat\Connector\Drivers\ISet $group
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function addSet(ISet $group)
    {
        if (array_key_exists($group->getId(), $this->channels))
            return;

        SlackClient::getInstance()->sendCall('POST', '/conversations.invite', [
            'channel' => $group->getId(),
            'users'   => implode(',', [$this->id]),
        ]);

        $this->channels->put($group->getId(), $group);
    }

    /**
     * @param \Warlof\Seat\Connector\Drivers\ISet $group
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function removeSet(ISet $group)
    {
        if (! array_key_exists($group->getId(), $this->channels))
            return;

        SlackClient::getInstance()->sendCall('POST', '/conversations.kick', [
            'channel' => $group->getId(),
            'user'    => $this->id,
        ]);

        $this->channels->pull($group->getId());
    }
}
