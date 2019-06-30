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

namespace Warlof\Seat\Slackbot\Jobs;

use Illuminate\Support\Collection;
use Warlof\Seat\Slackbot\Http\Controllers\Services\Traits\SlackApiConnector;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Models\SlackLog;

class Receptionist extends SlackJobBase {

    use SlackApiConnector;

    /**
     * @var int
     */
    public $delay = 60;

    /**
     * @var string
     */
    private $conversation_id;

    /**
     * @var Collection
     */
    private $pending_invitations;

    /**
     * @var array
     */
    protected $tags = ['receptionist'];

    /**
     * SyncUser constructor.
     * @param int|null $group_id
     */
    public function __construct(string $conversation_id, Collection $slack_user)
    {
        logger()->debug('Initialising conversation receptionist for ' . $conversation_id, ['inviting' => $slack_user->toArray()]);

        $this->conversation_id = $conversation_id;
        $this->pending_invitations = $slack_user;

        array_push($this->tags, 'conversation_id:' . $conversation_id);
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
    public function handle()
    {

        $this->getConnector()->setBody([
            'channel' => $this->conversation_id,
            'users'   => $this->pending_invitations->pluck('slack_id')->implode(','),
        ])->invoke( 'post', '/conversations.invite');

        $this->logInvitationEvents($this->pending_invitations);
    }

    /**
     * @param Collection $slackUsers
     */
    private function logInvitationEvents(Collection $slackUsers)
    {
        $slackChannel = SlackChannel::find($this->conversation_id);

        foreach ($slackUsers as $slackUser) {

            SlackLog::create([
                'event' => 'invite',
                'message' => sprintf('The user %s (%s) has been invited to the following channel : %s',
                    $slackUser->name, optional($slackUser->group->main_character)->name ?: 'Unknown Character', $slackChannel->name),
            ]);
        }
    }
}
