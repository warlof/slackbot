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

use Exception;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\Config;
use Warlof\Seat\Connector\Drivers\Slack\Driver\SlackClient;
use Warlof\Seat\Connector\Events\EventLogger;
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
        try {
            $settings = setting('seat-connector.drivers.slack', true);

            if (is_null($settings) || !is_object($settings))
                throw new DriverSettingsException('The Driver has not been configured yet.');

            if (!property_exists($settings, 'client_id') || is_null($settings->client_id) || $settings->client_id == '')
                throw new DriverSettingsException('Parameter client_id is missing.');

            if (!property_exists($settings, 'client_secret') || is_null($settings->client_secret) || $settings->client_secret == '')
                throw new DriverSettingsException('Parameter client_secret is missing.');

            if (!property_exists($settings, 'invitation_link') || is_null($settings->invitation_link) || $settings->invitation_link == '')
                throw new DriverSettingsException('Parameter invitation_link is missing.');

            $redirect_uri = route('seat-connector.drivers.slack.registration.callback');

            $config = new Config($settings->client_id, $settings->client_secret, $redirect_uri);

            return Socialite::driver('slack')->setConfig($config)->setScopes(['identity.basic', 'identity.email'])->redirect();
        } catch (Exception $e) {
            logger()->error($e->getMessage());
            event(new EventLogger('slack', 'critical', 'registration', $e->getMessage()));

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function handleProviderCallback()
    {
        try {
            $settings = setting('seat-connector.drivers.slack', true);

            if (is_null($settings) || ! is_object($settings))
                throw new DriverSettingsException('The Driver has not been configured yet.');

            if (! property_exists($settings, 'invitation_link') || is_null($settings->invitation_link) || $settings->invitation_link == '')
                throw new DriverSettingsException('Parameter invitation_link is missing.');

            $redirect_uri = route('seat-connector.drivers.slack.registration.callback');

            $config = new Config($settings->client_id, $settings->client_secret, $redirect_uri);

            $socialite_user = Socialite::driver('slack')->setConfig($config)->user();

            $nickname = $socialite_user->nickname ?: $socialite_user->name;
            $identity = $this->coupleUser(auth()->user()->group_id, $nickname, $socialite_user->id, $socialite_user->email);

            $client = SlackClient::getInstance();
            $user = $client->getUser($socialite_user->id);

            foreach ($identity->allowedSets() as $set_id) {
                $set = $client->getSet($set_id);

                if (is_null($set)) {
                    logger()->error(sprintf('Unable to retrieve Slack Channel with ID %s', $set_id));
                    event(new EventLogger('slack', 'error', 'registration',
                        sprintf('Unable to retrieve Slack Channel with ID %s', $set_id)));

                    continue;
                }

                $user->addSet($set);
            }

            $expected_nickname = $identity->buildConnectorNickname();
            $user->setName($expected_nickname);

            $identity->connector_name = $expected_nickname;
            $identity->save();

            return redirect()->to($settings->invitation_link);
        } catch (Exception $e) {
            logger()->error($e->getMessage());
            event(new EventLogger('slack', 'critical', 'registration', $e->getMessage()));

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * @param int $group_id
     * @param string $nickname
     * @param \Warlof\Seat\Connector\Drivers\IUser $user
     * @return \Warlof\Seat\Connector\Models\User
     */
    private function coupleUser(int $group_id, string $nickname, string $id, string $uid): User
    {
        $identity = User::updateOrCreate([
            'connector_type' => 'slack',
            'connector_id' => $id,
        ], [
            'connector_name' => $nickname,
            'group_id'       => auth()->user()->group_id,
            'unique_id'      => $uid,
        ]);

        event(new EventLogger('slack', 'notice', 'registration',
            sprintf('User %s (%d) has been registered with ID %s and UID %s',
                $nickname, $group_id, $id, $uid)));

        return $identity;
    }
}
