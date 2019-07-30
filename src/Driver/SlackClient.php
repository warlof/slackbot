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

use GuzzleHttp\Client;
use Warlof\Seat\Connector\Drivers\IClient;
use Warlof\Seat\Connector\Drivers\ISet;
use Warlof\Seat\Connector\Drivers\IUser;
use Warlof\Seat\Connector\Drivers\Slack\Exceptions\SlackException;
use Warlof\Seat\Connector\Exceptions\DriverSettingsException;

/**
 * Class SlackClient.
 *
 * @package Warlof\Seat\Connector\Drivers\Slack
 */
class SlackClient implements IClient
{
    const BASE_URI = 'https://slack.com/api/';

    /**
     * @var \Warlof\Seat\Connector\Drivers\Slack\Driver\SlackClient
     */
    private static $instance;

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * @var string
     */
    private $token;

    /**
     * @var \Warlof\Seat\Connector\Drivers\IUser[]
     */
    private $chatters;

    /**
     * @var \Warlof\Seat\Connector\Drivers\ISet[]
     */
    private $channels;

    /**
     * SlackClient constructor.
     *
     * @param array $parameters
     */
    private function __construct(array $parameters)
    {
        $this->token = $parameters['token'];

        $this->chatters  = collect();
        $this->channels = collect();
    }

    /**
     * @return \Warlof\Seat\Connector\Drivers\Slack\Driver\SlackClient
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public static function getInstance(): IClient
    {
        if (! isset(self::$instance)) {
            $settings = setting('seat-connector.drivers.slack', true);

            if (is_null($settings) || ! is_object($settings))
                throw new DriverSettingsException('The Driver has not been configured yet.');

            if (! property_exists($settings, 'token') || is_null($settings->token) || $settings->token == '')
                throw new DriverSettingsException('Parameter token is missing.');

            self::$instance = new SlackClient([
                'token' => $settings->token,
            ]);
        }

        return self::$instance;
    }

    /**
     * @return \Warlof\Seat\Connector\Drivers\IUser[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Drivers\Slack\Exceptions\SlackException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function getUsers(): array
    {
        if ($this->chatters->isEmpty())
            $this->seedChatters();

        return $this->chatters->toArray();
    }

    /**
     * @param string $id
     * @return \Warlof\Seat\Connector\Drivers\IUser|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Drivers\Slack\Exceptions\SlackException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function getUser(string $id): ?IUser
    {
        if ($this->chatters->isEmpty())
            $this->seedChatters();

        $chatter = $this->chatters->get($id);

        if (is_null($chatter)) {
            $body = SlackClient::getInstance()->sendCall('GET', '/users.info', [
                'user' => $id,
            ]);

            $chatter = new SlackChatter((array) $body->user);
            $this->chatters->put($chatter->getClientId(), $chatter);
        }

        return $chatter;
    }

    /**
     * @return \Warlof\Seat\Connector\Drivers\ISet[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Drivers\Slack\Exceptions\SlackException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function getSets(): array
    {
        if ($this->channels->isEmpty())
            $this->seedChannels();

        return $this->channels->toArray();
    }

    /**
     * @param string $id
     * @return \Warlof\Seat\Connector\Drivers\ISet|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Drivers\Slack\Exceptions\SlackException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function getSet(string $id): ?ISet
    {
        $group = $this->channels->get($id);

        if (is_null($group)) {
            $body = SlackClient::getInstance()->sendCall('GET', '/conversations.info', [
                'channel' => $id,
            ]);

            $group = new SlackChannel((array) $body->channel);
        }

        return $group;
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $arguments
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Warlof\Seat\Connector\Drivers\Slack\Exceptions\SlackException
     */
    public function sendCall(string $method, string $endpoint, array $arguments = [])
    {
        $uri = ltrim($endpoint, '/');

        if (is_null($this->client))
            $this->client = new Client([
                'base_uri' => self::BASE_URI,
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $this->token),
                ],
            ]);

        $response = $this->client->request($method, $uri, [
            'query' => $arguments,
        ]);

        $body = json_decode($response->getBody());

        if (! $body->ok)
            throw new SlackException($body->error);

        return $body;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Drivers\Slack\Exceptions\SlackException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    private function seedChannels()
    {
        $body = SlackClient::getInstance()->sendCall('GET', '/conversations.list', [
            'exclude_archived' => true,
            'types'            => implode(',', ['public_channel', 'private_channel']),
        ]);

        foreach ($body->channels as $attributes) {
            $channel = new SlackChannel((array) $attributes);
            $this->channels->put($channel->getId(), $channel);
        }
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Drivers\Slack\Exceptions\SlackException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    private function seedChatters()
    {
        $body = SlackClient::getInstance()->sendCall('GET', '/users.list');

        foreach ($body->members as $attributes) {
            $chatter = new SlackChatter((array) $attributes);
            $this->chatters->put($chatter->getClientId(), $chatter);
        }
    }
}
