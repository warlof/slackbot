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

use Illuminate\Support\Facades\Cache;
use Warlof\Seat\Slackbot\Http\Controllers\Services\Traits\SlackApiConnector;

class ConversationDispatcher extends SlackJobBase {

    use SlackApiConnector;

    /**
     * @var array
     */
    protected $tags = ['dispatcher'];

    /**
     * @var bool
     */
    private $terminator;

    /**
     * ConversationDispatcher constructor.
     * @param bool $terminator Determine if the dispatcher must run a massive kick
     */
    public function __construct(bool $terminator = false)
    {
        $this->terminator = $terminator;

        // in case terminator mode is active, append terminator to tags
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
    public function handle() {

        logger()->debug('Slack Receptionist - Starting job...');

        // resetting cache before queuing jobs
        Cache::tags(['conversations', 'members'])->flush();

        // retrieve information related to the current token
        // so we can remove the user owner from process since we're not able to do things on ourselves
        $token_info = $this->getConnector()->invoke('get', '/auth.test');

        logger()->debug('Slack Receptionist - Checking token', [
            'owner' => $token_info->user_id,
        ]);

        // retrieving all conversation from Slack team
        $conversations = $this->fetchSlackConversations();

        foreach ($conversations as $conversation) {

            // ignoring general channel since it's self handled
            if ($conversation->is_general)
                continue;

            // preparing a new orchestrator tied to the current conversation
            $job = new ConversationOrchestrator($conversation->id, $token_info);

            // in case the dispatcher is running with massive kick flag, forward it to the orchestrator
            if ($this->terminator)
                $job->setTerminatorFlag();

            // queuing a new orchestrator for that conversation which will handle delay between kick and invitation
            dispatch($job);
        }
    }
}
