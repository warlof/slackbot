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
use Warlof\Seat\Slackbot\Models\SlackUser;

class ConversationOrchestrator extends SlackJobBase {

    use SlackApiConnector;

    /**
     * @var string
     */
    private $conversation_id;

    /**
     * @var array
     */
    protected $tags = ['orchestrator'];

    /**
     * ConversationHandler constructor.
     * @param string $conversation_id
     */
    public function __construct(string $conversation_id)
    {
        logger()->debug('Initialising conversation orchestrator for ' . $conversation_id);

        $this->conversation_id = $conversation_id;

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

        $pending_kicks = collect();
        $pending_invitations = collect();

        // resetting cache before queuing jobs
        Cache::tags(['conversations', 'members'])->flush();

        // retrieving mapped user list
        $users = SlackUser::where('slack_id', '<>', $this->owner->user_id)->get();

        // retrieving all members of the conversation
        $members = $this->fetchSlackConversationMembers($this->conversation_id);

        // checking for each user who have to be invite and kick
        foreach ($users as $user) {

            if (Helper::isAllowedChannel($this->conversation_id, $user) && ! in_array($user->slack_id, $members))
                $pending_invitations->push($user);

            if (! Helper::isAllowedChannel($this->conversation_id, $user) && in_array($user->slack_id, $members))
                $pending_kicks->push($user);

        }

        // spacing invitation job with a list of 40 IDs every minute in order to avoid API threshold
        // references : https://api.slack.com/docs/rate-limits#tier_t3 | https://api.slack.com/methods/conversations.invite
        $pending_invitations->chunk(40)->each(function ($slack_users) {
            dispatch(new Receptionist($this->conversation_id, $slack_users))->delay(60);
        });

        // spacing kick job with a batch of 40 every minute in order to avoid API threshold
        // references : https://api.slack.com/docs/rate-limits#tier_t3 | https://api.slack.com/methods/conversations.kick
        $pending_kicks->chunk(40)->each(function ($slack_users) {
            dispatch(new AssKicker($this->conversation_id, $slack_users))->delay(60);
        });
    }
}
