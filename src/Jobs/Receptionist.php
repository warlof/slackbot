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

class Receptionist extends SlackJobBase {

    use SlackApiConnector;

    /**
     * @var array
     */
    private $seat_group_ids = [];

    /**
     * @var array
     */
    private $pending_invitations = [];

    /**
     * @var array
     */
    protected $tags = ['receptionist'];


    /**
     * SyncUser constructor.
     * @param int|null $group_id
     */
    public function __construct(int $group_id = null)
    {
        if (! is_null($group_id))
            $this->seat_group_id = $group_id;
    }

    /**
     * @param array $group_ids
     */
    public function setSeatGroupId(array $group_ids)
    {
        $this->seat_group_ids = $group_ids;
    }

    /**
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Exceptions\SlackSettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    public function handle() {

        logger()->debug('Slack Receptionist - Starting job...');

        // retrieve information related to the current token
        // so we can remove the user owner from process since we're not able to do things on ourselves
        $token_info = $this->getConnector()->invoke('get', '/auth.test');
        logger()->debug('Slack Receptionist - Checking token', [
            'owner' => $token_info->user_id,
        ]);

        $query = SlackUser::where('slack_id', '<>', $token_info->user_id);

        if (count($this->seat_group_ids) > 0) {
            $query->whereIn('group_id', $this->seat_group_ids);
            logger()->debug('Slack Receptionist - Restricting job to user groups : ' . $this->seat_group_ids);
        }

        $users = $query->get();

        foreach ($users as $user) {

            $granted_channels = Helper::allowedChannels($user);

            logger()->debug('Slack Receptionist - Retrieving granted channels list', [
                'user'     => [
                    'seat'  => $user->group_id,
                    'slack' => $user->slack_id,
                ],
                'channels' => $granted_channels,
            ]);

            foreach ($granted_channels as $channel_id) {
                $members = $this->fetchSlackConversationMembers($channel_id);

                // if user is not already member of the channel, put Slack ID in queue
                if (!in_array($user->slack_id, $members)) {
                    logger()->debug('Slack reception - buffering invitation', [
                        'user'     => [
                            'seat'  => $user->group_id,
                            'slack' => $user->slack_id,
                        ],
                        'channel_id'    => $channel_id,
                    ]);

                    if (!array_key_exists($channel_id, $this->pending_invitations)) {
                        $this->pending_invitations[$channel_id] = [];
                    }

                    $this->pending_invitations[$channel_id][] = $user->slack_id;
                }
            }
        }

        logger()->debug('Receptionist - clearing cached data');
        Cache::tags(['conversations', 'members'])->flush();

        foreach ($this->pending_invitations as $channel_id => $user_list) {

            logger()->debug('Slack Receptionist - Starting invitation', [
                'channel' => $channel_id,
                'users'   => $user_list,
            ]);

            // split user list into sub list of maximum 30 user ID
            // in order to send less invitation queries as possible
            foreach (collect($user_list)->chunk(30)->toArray() as $user_chunk) {
                $this->getConnector()->setBody([
                    'channel' => $channel_id,
                    'users'   => implode( ',', $user_chunk ),
                ])->invoke( 'post', '/conversations.invite');

                $this->logInvitationEvents($channel_id, $user_chunk);
                sleep(1);
            }
        }
    }

    private function logInvitationEvents(string $channel_id, array $user_ids)
    {
        foreach ($user_ids as $user) {
            $slackUser = SlackUser::where('slack_id', $user)->first();
            $slackChannel = SlackChannel::find($channel_id);

            SlackLog::create([
                'event' => 'invite',
                'message' => sprintf('The user %s (%s) has been invited to the following channel : %s',
                    $slackUser->name, $slackUser->group->users->first()->name, $slackChannel->name),
            ]);
        }
    }
}
