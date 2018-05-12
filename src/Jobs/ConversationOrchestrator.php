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

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Warlof\Seat\Slackbot\Helpers\Helper;
use Warlof\Seat\Slackbot\Http\Controllers\Services\Traits\SlackApiConnector;
use Warlof\Seat\Slackbot\Models\SlackUser;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackResponse;

class ConversationOrchestrator extends SlackJobBase {

    use SlackApiConnector;

    /**
     * @var SlackResponse
     */
    protected $owner;

    /**
     * @var array
     */
    protected $tags = ['orchestrator'];

    /**
     * @var string
     */
    private $conversation_id;

    /**
     * @var bool
     */
    private $terminator;

    /**
     * ConversationOrchestrator constructor.
     * @param string $conversation_id
     * @param SlackResponse $token_info
     * @param bool $terminator Determine if the orchestrator must run a massive kick
     */
    public function __construct(string $conversation_id, SlackResponse $token_info, bool $terminator = false)
    {
        logger()->debug('Initialising conversation orchestrator for ' . $conversation_id);

        $this->owner = $token_info;
        $this->terminator = $terminator;
        $this->conversation_id = $conversation_id;

        array_push($this->tags, 'conversation_id:' . $conversation_id);

        // if the terminator flag has been passed, append terminator into tags
        if ($this->terminator)
            array_push($this->tags, 'terminator');
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

        // resetting cache before queuing jobs
        Cache::tags(['conversations', 'members'])->flush();

        // collect all user in the channel
        $members = $this->fetchSlackConversationMembers($this->conversation_id);

        // in case terminator flag has not been specified, proceed using user defined mapping
        if (! $this->terminator) {
            $this->processMappingBase($members);
            return;
        }

        // in case terminator flag has been specified, proceed using conversation members list
        $this->handleKicks(collect($members));
    }

    /**
     * Set terminator flag to true
     */
    public function setTerminatorFlag()
    {
        $this->terminator = true;

        if (! in_array('terminator', $this->tags))
            array_push($this->tags, 'terminator');
    }

    /**
     * @param array $members
     */
    private function processMappingBase(array $members)
    {
        $pending_kicks = collect();
        $pending_invitations = collect();

        // retrieving mapped user list
        $users = SlackUser::where('slack_id', '<>', $this->owner->user_id)->get();

        // checking for each user who have to be invite and kick
        foreach ($users as $user) {

            if (Helper::isAllowedChannel($this->conversation_id, $user) && ! in_array($user->slack_id, $members))
                $pending_invitations->push($user);

            if (! Helper::isAllowedChannel($this->conversation_id, $user) && in_array($user->slack_id, $members))
                $pending_kicks->push($user);

        }

        $this->handleReceptions($pending_invitations);
        $this->handleKicks($pending_kicks);
    }

    /**
     * @param Collection $pending_invitations
     */
    private function handleReceptions(Collection $pending_invitations)
    {
        // spacing invitation job with a list of 40 IDs every minute in order to avoid API threshold
        // references : https://api.slack.com/docs/rate-limits#tier_t3 | https://api.slack.com/methods/conversations.invite
        $batches = $pending_invitations->chunk(40);
        $chained_jobs = collect();

        // prepare chained jobs
        $batches->splice(1)->each(function ($slack_users) use ($chained_jobs) {
            $chained_jobs->push(new Receptionist($this->conversation_id, $slack_users));
        });

        // if we have at least 1 element for which queuing a job, spawn the main job
        if ($batches->count() > 0)
            $queued_job = dispatch(new Receptionist($this->conversation_id, $batches->first()))->delay(0);

        // append every other chained job in queue of the main job
        if ($batches->count() > 1)
            $queued_job->chain($chained_jobs->toArray());
    }

    /**
     * @param Collection $pending_kicks
     */
    private function handleKicks(Collection $pending_kicks)
    {
        // spacing kick job with a batch of 40 every minute in order to avoid API threshold
        // references : https://api.slack.com/docs/rate-limits#tier_t3 | https://api.slack.com/methods/conversations.kick
        $batches = $pending_kicks->chunk(40);
        $chained_jobs = collect();

        // prepare chained jobs
        $batches->splice(1)->each(function ($slack_users) use ($chained_jobs) {
            $chained_jobs->push(new AssKicker($this->conversation_id, $slack_users, $this->terminator));
        });

        // if we have at least 1 element for which queuing a job, spawn the main job
        if ($batches->count() > 0)
            $queued_job = dispatch(new AssKicker($this->conversation_id, $batches->first(), $this->terminator))->delay(0);

        // append every other chained job in queue of the main job
        if ($batches->count() > 1)
            $queued_job->chain($chained_jobs->toArray());
    }
}
