<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 19/12/2017
 * Time: 14:51
 */

namespace Warlof\Seat\Slackbot\Http\Controllers\Services\Traits;


use Illuminate\Support\Facades\Cache;
use Monolog\Logger;
use Warlof\Seat\Slackbot\Exceptions\SlackSettingException;
use Warlof\Seat\Slackbot\Repositories\Slack\Configuration;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackAuthentication;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackConfiguration;
use Warlof\Seat\Slackbot\Repositories\Slack\SlackApi;

trait SlackApiConnector {

    /**
     * @var SlackApi
     */
    private $slack;

    /**
     * @var array Scopes
     */
    private $scopes = [
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
    ];

    /**
     * @return SlackApi
     * @throws SlackSettingException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
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
            'http_user_agent'     => '(Clan Daerie;Warlof Tutsimo;Daerie Inc.;Get Off My Lawn)',
            'logger_level'        => Logger::DEBUG,
            'logfile_location'    => storage_path('logs/slack.log'),
            'file_cache_location' => storage_path('cache/slack/'),
        ]));

        $this->slack = new SlackApi(new SlackAuthentication([
            'access_token' => setting('warlof.slackbot.credentials.access_token', true),
            'scopes' => $this->scopes,
        ]));

        return $this->slack;
    }

    /**
     * @param string|null $cursor
     *
     * @return array
     * @throws SlackSettingException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    private function fetchSlackConversations(string $cursor = null) : array
    {
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
            sleep(1);
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
     *
     * @return array
     * @throws SlackSettingException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    private function fetchSlackConversationMembers(string $channel_id, string $cursor = null) : array
    {
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
            $response = $this->getConnector()->invoke( 'get', '/conversations.members' );
            Cache::tags(['conversations', 'members'])->put(is_null($cursor) ? 'root' : $cursor, $response);
        }

        logger()->debug('Slack reception - channel members', [
            'channel_id' => $channel_id,
            'members' => $response->members,
        ]);

        $members = $response->members;

        if (property_exists($response, 'response_metadata') && $response->response_metadata->next_cursor != '') {
            sleep(1);
            $members = array_merge(
                $members,
                $this->fetchSlackConversationMembers($channel_id, $response->response_metadata->next_cursor));
        }

        return $members;
    }

}