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

namespace Warlof\Seat\Slackbot\Jobs;

use Illuminate\Support\Facades\Cache;
use Warlof\Seat\Slackbot\Helpers\Helper;
use Warlof\Seat\Slackbot\Http\Controllers\Services\Traits\SlackApiConnector;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Models\SlackLog;
use Warlof\Seat\Slackbot\Models\SlackUser;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException;

class AssKicker extends SlackJobBase {

    use SlackApiConnector;

    /**
     * @var array
     */
    protected $tags = ['ass-kicker'];

    /**
     * @var array
     */
    private $seat_group_ids = [];

    /**
     * @throws RequestFailedException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Exceptions\SlackSettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    public function handle()
    {

        $token_info = $this->getConnector()->invoke('get', '/auth.test');
        logger()->debug('Slack Receptionist - Checking token', [
            'owner' => $token_info->user_id,
        ]);

        $query = SlackUser::where('slack_id', '<>', $token_info->user_id);

        if (count($this->seat_group_ids) > 0) {
            $query->whereIn('group_id', $this->seat_group_ids);
            logger()->debug('Slack Ass Kicker - Restricting job to user groups : ' . $this->seat_group_ids);
        }

        $users = $query->get();

        $channels = $this->fetchSlackConversations();

        logger()->debug('Slack Kicker - channels list', $channels);

        foreach ($channels as $channel) {

            if ($channel->is_general)
                continue;

            $members = $this->fetchSlackConversationMembers($channel->id);

            logger()->debug('Slack Kicker - Channel members', [
                'channel' => $channel->id,
                'members' => $members
            ]);

            foreach ($users as $slack_user) {

                logger()->debug('Slack Kicker - Checking user', [
                    'user'    => $slack_user,
                    'channel' => $channel->id,
                    'members' => $members,
                ]);

                if (!in_array($slack_user->slack_id, $members))
                    continue;

                $granted_channels = Helper::allowedChannels($slack_user);

                logger()->debug('Slack Kicker - Granted channels', [
                    'user'     => [
                        'seat'  => $slack_user->group_id,
                        'slack' => $slack_user->slack_id,
                    ],
                    'channels' => $granted_channels,
                ]);

                if (! in_array($channel->id, $granted_channels)) {
                    logger()->debug('Slack Kicker - Kicking user', [
                        'user'    => [
                            'seat'  => $slack_user->group_id,
                            'slack' => $slack_user->slack_id
                        ],
                        'channel' => $channel->id
                    ]);

                    try {

                        $this->getConnector()->setBody([
                            'channel' => $channel->id,
                            'user'    => $slack_user->slack_id,
                        ])->invoke('post', '/conversations.kick');

                    } catch (RequestFailedException $e) {

                        // catch error related to unknown member
                        if ($e->getError() == 'invalid_membership') {
                            $slack_user->delete();
                            continue;
                        }

                        // if error is not related to unknown member, just forward the initial exception
                        throw $e;
                    }

                    $this->logKickEvent($channel->id, $slack_user->slack_id);
                    sleep(1);
                }
            }

        }

        logger()->debug('Slack kicker - clearing cached data');
        Cache::tags(['conversations', 'members'])->flush();
    }

    private function logKickEvent(string $channel_id, string $group_id)
    {
        $slackUser = SlackUser::where('slack_id', $group_id)->first();
        $slackChannel = SlackChannel::find($channel_id);

        SlackLog::create([
            'event' => 'kick',
            'message' => sprintf('The user %s (%s) has been kicked from the following channel : %s',
                $slackUser->name, $slackUser->group->main_character->name, $slackChannel->name),
        ]);
    }

}
