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

use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\Config;
use Warlof\Seat\Connector\Exceptions\DriverSettingsException;
use Warlof\Seat\Connector\Models\User;

/**
 * Class RegistrationController.
 *
 * @package Warlof\Seat\Connector\Drivers\Slack\Http\Controllers
 */
class RegistrationController
{
    /**
     * @return mixed
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function redirectToProvider()
    {
        $settings = setting('seat-connector.drivers.slack', true);

        if (is_null($settings) || ! is_object($settings))
            throw new DriverSettingsException('The Driver has not been configured yet.');

        if (! property_exists($settings, 'client_id') || is_null($settings->client_id) || $settings->client_id == '')
            throw new DriverSettingsException('Parameter client_id is missing.');

        if (! property_exists($settings, 'client_secret') || is_null($settings->client_secret) || $settings->client_secret == '')
            throw new DriverSettingsException('Parameter client_secret is missing.');

        if (! property_exists($settings, 'invitation_link') || is_null($settings->invitation_link) || $settings->invitation_link == '')
            throw new DriverSettingsException('Parameter invitation_link is missing.');

        $redirect_uri = route('seat-connector.drivers.slack.registration.callback');

        $config = new Config($settings->client_id, $settings->client_secret, $redirect_uri);

        return Socialite::with('slack')->setConfig($config)->setScopes(['identity.basic', 'identity.email'])->redirect();
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function handleProviderCallback()
    {
        $settings = setting('seat-connector.drivers.slack', true);

        if (is_null($settings) || ! is_object($settings))
            throw new DriverSettingsException('The Driver has not been configured yet.');

        if (! property_exists($settings, 'invitation_link') || is_null($settings->invitation_link) || $settings->invitation_link == '')
            throw new DriverSettingsException('Parameter invitation_link is missing.');

        $socialite_user = Socialite::driver('slack')->user();

        User::updateOrCreate([
            'connector_type' => 'slack',
            'connector_id' => $socialite_user->id,
        ], [
            'connector_name' => $socialite_user->nickname ?: $socialite_user->name,
            'group_id'       => auth()->user()->group_id,
            'unique_id'      => $socialite_user->email,
        ]);

        return redirect()->to($settings->invitation_link);
    }
}
