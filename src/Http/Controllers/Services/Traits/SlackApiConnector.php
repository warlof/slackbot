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

namespace Warlof\Seat\Slackbot\Http\Controllers\Services\Traits;

use Illuminate\Support\Facades\Cache;
use Warlof\Seat\Slackbot\Exceptions\SlackSettingException;
use Warlof\Seat\Slackbot\Repositories\Slack\Configuration;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackAuthentication;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackConfiguration;
use Warlof\Seat\Slackbot\Repositories\Slack\Log\LogglyLogger;
use Warlof\Seat\Slackbot\Repositories\Slack\SlackApi;

trait SlackApiConnector {

    /**
     * @var SlackApi
     */
    private $slack;

    /**
     * @return SlackApi
     * @throws SlackSettingException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     */
    private function getConnector() : SlackApi
    {
        if (!is_null($this->slack))
            return $this->slack;

        if (is_null(setting('warlof.slackbot.credentials.access_token', true)))
            throw new SlackSettingException("warlof.slackbot.credentials.access_token is missing in settings. " .
                                            "Ensure you've link SeAT to a valid Slack Team.");

        $configuration = Configuration::getInstance();
        $configuration->setConfiguration(new SlackConfiguration([
            'http_user_agent'     => '(Warlof Tutsimo;Loic Leuilliot;e.elfaus@gmail.com)',
            'logger'              => LogglyLogger::class,
            'logger_level'        => config('app.log_level'),
            'logfile_location'    => storage_path('logs/slack.log'),
            'file_cache_location' => storage_path('cache/slack/'),
        ]));

        $this->slack = new SlackApi(new SlackAuthentication([
            'access_token' => setting('warlof.slackbot.credentials.access_token', true),
            'scopes' => [
                'users:read',
                'users:read.email',
                'channels:read',
                'channels:write',
                'groups:read',
                'groups:write',
                'im:read',
                'im:write',
                'mpim:read',
                'mpim:write',
                'read',
                'post',
            ],
        ]));

        return $this->slack;
    }

    /**
     * @param string $slack_id
     * @param string|null $cursor
     * @return array
     * @throws SlackSettingException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    private function fetchUserConversations(string $slack_id, string $cursor = null)
    {
        sleep(1);

        $query_parameters = [
            'types' => implode(',', ['public_channel', 'private_channel']),
            'exclude_archived' => true,
            'user' => $slack_id,
        ];

        $this->getConnector()->setQueryString($query_parameters);

        if (! is_null($cursor))
            $query_parameters['cursor'] = $cursor;

        $response = $this->getConnector()->invoke('get', '/users.conversations');
        $channels = $response->channels;

        if (property_exists($response, 'response_metadata') && $response->response_metadata->next_cursor != '') {
            $channels = array_merge(
                $channels,
                $this->fetchUserConversations($slack_id, $response->response_metadata->next_cursor)
            );
        }

        return $channels;
    }

    /**
     * @param string|null $cursor
     * @return array
     * @throws SlackSettingException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    private function fetchSlackConversations(string $cursor = null) : array
    {
        sleep(1);

        $this->getConnector()->setQueryString([
            'types' => implode(',', ['public_channel', 'private_channel']),
            'exclude_archived' => true,
        ]);

        if (!is_null($cursor))
            $this->getConnector()->setQueryString([
                'cursor' => $cursor,
                'types' => implode(',', ['public_channel', 'private_channel']),
                'exclude_archived' => true,
            ]);

        $response = Cache::tags(['conversations'])->get(is_null($cursor) ? 'root' : $cursor);

        if (is_null($response)) {
            $response = $this->getConnector()->invoke('get', '/conversations.list');
            Cache::tags(['conversations'])->put(is_null($cursor) ? 'root' : $cursor, $response);
        }

        $channels = $response->channels;

        if (property_exists($response, 'response_metadata') && $response->response_metadata->next_cursor != '') {
            $channels = array_merge(
                $channels,
                $this->fetchSlackConversations( $response->response_metadata->next_cursor)
            );
        }

        return $channels;
    }

    /**
     * @param string $channel_id
     * @param string|null $cursor
     * @return array
     * @throws SlackSettingException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    private function fetchSlackConversationMembers(string $channel_id, string $cursor = null) : array
    {
        sleep(1);

        $this->getConnector()->setQueryString([
            'channel' => $channel_id,
        ]);

        if (!is_null($cursor))
            $this->getConnector()->setQueryString([
                'channel' => $channel_id,
                'cursor' => $cursor,
            ]);

        $response = Cache::tags(['conversations', 'members'])->get(is_null($cursor) ? 'root' : $cursor);

        if (is_null($response)) {
            $response = $this->getConnector()->invoke('get', '/conversations.members');
            Cache::tags(['conversations', 'members'])->put(is_null($cursor) ? 'root' : $cursor, $response);
        }

        logger()->debug('Slack reception - channel members', [
            'channel_id' => $channel_id,
            'members' => $response->members,
        ]);

        $members = $response->members;

        if (property_exists($response, 'response_metadata') && $response->response_metadata->next_cursor != '') {
            $members = array_merge(
                $members,
                $this->fetchSlackConversationMembers(
                    $channel_id,
                    $response->response_metadata->next_cursor));
        }

        return $members;
    }

}
