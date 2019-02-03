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
use Warlof\Seat\Slackbot\Models\SlackUser;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException;

class AssKicker extends SlackJobBase {

    use SlackApiConnector;

    /**
     * @var int
     */
    public $delay = 60;

    /**
     * @var array
     */
    protected $tags = ['ass-kicker'];

    /**
     * @var string
     */
    private $conversation_id;

    /**
     * @var Collection
     */
    private $pending_kicks;

    /**
     * @var bool
     */
    private $terminator;

    /**
     * AssKicker constructor.
     * @param string $conversation_id
     * @param Collection $slack_users
     * @param bool $terminator Determine if the ass kicker must run a massive kick
     */
    public function __construct(string $conversation_id, Collection $slack_users, bool $terminator = false)
    {
        logger()->debug('Instancing conversation ass-kick for ' . $conversation_id, ['kicking' => $slack_users->toArray()]);

        $this->terminator = $terminator;
        $this->conversation_id = $conversation_id;
        $this->pending_kicks = $slack_users;

        array_push($this->tags, 'conversation_id:' . $conversation_id);

        if ($this->terminator)
            array_push($this->tags, 'terminator');
    }

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

        $slackChannel = SlackChannel::find($this->conversation_id);

        $this->pending_kicks->each(function ($user) use ($slackChannel) {

            try {

                $this->getConnector()->setBody([
                    'channel' => $this->conversation_id,
                    // if we're in terminator mode, user is a user id instead a Slack User object
                    'user'    => $this->terminator ? $user : $user->slack_id,
                ])->invoke('post', '/conversations.kick');

                if (! $this->terminator)
                    $this->logKickEvent($slackChannel, $user);

            } catch (RequestFailedException $e) {

                if ($e->getError() != 'invalid_membership')
                    throw $e;

                if (! $this->terminator)
                    $user->delete();

            }

        });
    }

    /**
     * @param SlackChannel $slackChannel
     * @param SlackUser $slackUser
     */
    private function logKickEvent(SlackChannel $slackChannel, SlackUser $slackUser)
    {
        SlackLog::create([
            'event' => 'kick',
            'message' => sprintf('The user %s (%s) has been kicked from the following channel : %s',
                $slackUser->name, optional($slackUser->group->main_character)->name ?: 'Unknown Character', $slackChannel->name),
        ]);
    }

}
