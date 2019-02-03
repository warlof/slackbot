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

namespace Warlof\Seat\Slackbot\Http\Controllers\Services\Traits;

use Illuminate\Http\JsonResponse;
use Warlof\Seat\Slackbot\Exceptions\SlackSettingException;
use Warlof\Seat\Slackbot\Models\SlackChannel;

trait ConversationHandler
{
    use SlackApiConnector;

    private $conversationEvents = [
        'channel_created', 'group_created', 'channel_deleted', 'group_deleted',
        'channel_archive', 'group_archive', 'channel_unarchive', 'group_unarchive',
        'channel_rename', 'group_rename',
    ];

    /**
     * @param $channel
     * @throws SlackSettingException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    private function createConversation($channel)
    {
        // update database information
        SlackChannel::create([
            'id' => $channel['id'],
            'name' => $channel['name'],
            'is_group' => (strpos($channel['id'], 'C') === 0) ? false : true,
            'is_general' => false
        ]);

        $tokenInfo = $this->getConnector()->invoke('get', '/auth.test');

        // invite token owner in case he's not the channel creator
        if ($tokenInfo->user_id != $channel['creator']) {
            $this->getConnector()->setBody([
                'channel' => $channel['id'],
                'users' => $tokenInfo->user_id,
            ])->invoke('post', '/conversations.invite');
        }
    }

    private function deleteConversation($channelId)
    {
        // update database information
        if ($channel = SlackChannel::find($channelId))
            $channel->delete();
    }

    private function renameConversation($channel)
    {
        if ($channel = SlackChannel::find($channel['id']))
            $channel->update([
                'name' => $channel['name']
            ]);
    }

    private function archiveConversation($channelId)
    {
        if ($channel = SlackChannel::find($channelId)) {
            $channel->delete();
        }
    }

    /**
     * @param $channelId
     * @throws SlackSettingException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    private function unarchiveConversation($channelId)
    {
        $channel = $this->getConnector()->setQueryString([
            'channel' => $channelId,
        ])->invoke('get', '/conversations.info');

        // update database information
        SlackChannel::create([
            'id' => $channel->id,
            'name' => $channel->name,
            'is_group' => $channel->is_group,
            'is_general' => false
        ]);
    }

    /**
     * @param array $event
     * @return JsonResponse
     * @throws SlackSettingException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    private function eventConversationHandler(array $event) : JsonResponse
    {
        if (in_array($event['type'], ['channel_created', 'group_created'])) {
            $this->createConversation($event['channel']);
        }

        if (in_array($event['type'], ['channel_deleted', 'group_deleted'])) {
            $this->deleteConversation($event['channel']);
        }

        if (in_array($event['type'], ['channel_archive', 'group_archive'])) {
            $this->archiveConversation($event['channel']);
        }

        if (in_array($event['type'], ['channel_unarchive', 'group_unarchive'])) {
            $this->unarchiveConversation($event['channel']);
        }

        if (in_array($event['type'], ['channel_rename', 'group_rename'])) {
            $this->renameConversation($event['channel']);
        }

        return response()->json(['ok' => true], 200);
    }
}
