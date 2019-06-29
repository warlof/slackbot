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

namespace Warlof\Seat\Slackbot\Http\Controllers;

use Seat\Eveapi\Models\Character\CharacterInfo;
use Seat\Services\Models\UserSetting;
use Seat\Web\Http\Controllers\Controller;
use Warlof\Seat\Slackbot\Http\Controllers\Services\Traits\SlackApiConnector;
use Warlof\Seat\Slackbot\Models\SlackUser;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException;

class SlackbotController extends Controller
{
    use SlackApiConnector;

    public function getUsers()
    {
        return view('slackbot::users.list');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeUserMapping()
    {
        $slack_id = request()->input('slack_id');

        if ($slack_id == '')
            return redirect()->back('error', 'An error occurred while processing the request.');

        if (($slack_user = SlackUser::with('group')->where('slack_id', $slack_id)->first()) == null)
            return redirect()->back()->with('error', sprintf(
                'System cannot find any suitable mapping for Slack (%s).', $slack_id));

        $slack_user->delete();

        return redirect()->back()->with('success', 'System successfully remove the mapping between SeAT (' .
            $slack_user->group->name . ') and Slack (' . $slack_user->name . ').');
    }

    /**
     * @return mixed
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Exceptions\SlackSettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    public function getUsersData()
    {
        if (is_null(setting('warlof.slackbot.credentials.access_token', true)))
            return app('DataTables')::of(collect([]))->make(true);

        $this->refreshSlackUsername();

        $users = SlackUser::query()
            ->leftJoin((new UserSetting())->getTable(), function ($join) {
                $join->on((new SlackUser())->getTable() . '.group_id', '=', (new UserSetting())->getTable() . '.group_id')
                     ->where((new UserSetting())->getTable() . '.name', '=', 'main_character_id');
            })
            ->leftJoin((new CharacterInfo())->getTable(), 'character_id', '=', 'value')
            ->select(
                (new SlackUser())->getTable() . '.*',
                (new UserSetting())->getTable() . '.value AS user_id',
                (new CharacterInfo())->getTable() . '.name AS user_name'
            );

        return app('DataTables')::of($users)
            ->make(true);
    }

    private function refreshSlackUsername()
    {
        $users = SlackUser::with('group')->whereNull('name')->get();

        if ($users->count() > 0) {

            foreach ($users as $slackUser) {

                try {
                    $response = $this->getConnector()->setQueryString([
                        'email' => $slackUser->group->email,
                    ])->invoke( 'get', '/users.lookupByEmail' );

                    $slackUser->update([
                        'name' => property_exists($response->user, 'name') ? $response->user->name : '',
                    ]);

                    if ($users->count() > 1) {
                        sleep(1);
                    }
                } catch (RequestFailedException $e) {

                }

            }
        }
    }

}
