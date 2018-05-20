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
use Seat\Web\Models\Group;
use Seat\Web\Models\User;
use Warlof\Seat\Slackbot\Http\Controllers\Services\Traits\SlackApiConnector;
use Warlof\Seat\Slackbot\Models\SlackLog;
use Warlof\Seat\Slackbot\Models\SlackUser;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException;

class SyncUser extends SlackJobBase {

    use SlackApiConnector;

    /**
     * @var array
     */
    protected $tags = ['sync', 'users'];

    /**
     * @var array
     */
    private $seat_group_ids = [];

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
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    public function handle()
    {
        // retrieve all group
        $query = Group::select('id');

        // if command has been run for a specific user, restrict result on it
        if (! empty($this->seat_group_ids))
            $query->whereIn('id', $this->seat_group_ids);

        // excluding mapped group and group without email address
        $groups = $query->get()->whereNotIn('id', SlackUser::select('group_id')->get()->toArray())->filter(function($group) {
            return ! empty($group->email);
        });

        $users = User::whereIn('id', $groups->pluck('main_character_id')->toArray())->get();

        $this->bindingSlackUser($users);
    }

    /**
     * @param Collection $users
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Exceptions\SlackSettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    private function bindingSlackUser(Collection $users)
    {
        logger()->debug('bindingSlackUser', ['users' => $users]);

        foreach ($users as $user) {

            try {

                $response = $this->getConnector()->setQueryString([
                    'email' => $user->email,
                ]) ->invoke('get', '/users.lookupByEmail');

                SlackUser::create([
                    'group_id' => $user->group_id,
                    'slack_id' => $response->user->id,
                    'name' => property_exists($response->user, 'name') ? $response->user->name : '',
                ]);

                SlackLog::create([
                    'event' => 'binding',
                    'message' => sprintf('User %s (%s) has been successfully bind to %s',
                        $user->name,
                        $user->email,
                        property_exists($response->user, 'name') ? $response->user->name : ''),
                ]);

                sleep(1);

            } catch (RequestFailedException $e) {

                if ($e->getResponse()->error() == 'users_not_found') {
                    SlackLog::create([
                        'event'   => 'sync',
                        'message' => sprintf( 'Unable to retrieve Slack user for user %s (%s)', $user->name, $user->email ),
                    ]);
                } else {
                    SlackLog::create([
                        'event' => 'error',
                        'message' => sprintf('Slack respond with an unknown message while syncing %s (%s) : %s',
                            $user->name,
                            $user->email,
                            $e->getResponse()->error()),
                    ]);
                }

            }

        }
    }

}
