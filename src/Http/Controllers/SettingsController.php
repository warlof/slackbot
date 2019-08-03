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

namespace Warlof\Seat\Connector\Drivers\Slack\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Seat\Web\Http\Controllers\Controller;
use SocialiteProviders\Manager\Config;

/**
 * Class SettingsController.
 *
 * @package Warlof\Seat\Connector\Drivers\Slack\Http\Controllers
 */
class SettingsController extends Controller
{
    const SCOPES = [
        'channels:read',
        'channels:write',
        'groups:read',
        'groups:write',
        'users:read',
        'users.profile:write',
    ];

    /**
     * @param \Illuminate\Http\Request $request
     * @return mixed
     * @throws \Seat\Services\Exceptions\SettingException
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_id'       => 'required|string',
            'client_secret'   => 'required|string',
            'invitation_link' => 'required|url',
        ]);

        $settings = (object) [
            'client_id'       => (string) $request->input('client_id'),
            'client_secret'   => $request->input('client_secret'),
            'invitation_link' => $request->input('invitation_link'),
        ];

        setting(['seat-connector.drivers.slack', $settings], true);

        $redirect_uri = route('seat-connector.drivers.slack.settings.callback');

        $config = new Config($settings->client_id, $settings->client_secret, $redirect_uri);

        return Socialite::driver('slack')
            ->setConfig($config)
            ->setScopes(self::SCOPES)
            ->redirect();
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Seat\Services\Exceptions\SettingException
     */
    public function handleProviderCallback()
    {
        $settings = setting('seat-connector.drivers.slack', true);

        $redirect_uri = route('seat-connector.drivers.slack.settings.callback');

        $config = new Config($settings->client_id, $settings->client_secret, $redirect_uri);

        $socialite_user = Socialite::driver('slack')->setConfig($config)->user();

        $settings->token           = $socialite_user->token;
        $settings->organization_id = $socialite_user->organization_id;

        setting(['seat-connector.drivers.slack', $settings], true);

        return redirect()->route('seat-connector.settings')
            ->with('success', 'Slack settings has successfully been updated.');
    }
}
