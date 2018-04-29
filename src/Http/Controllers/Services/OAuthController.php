<?php
/**
 * This file is part of seat-slackbot and provide user synchronization between both SeAT and a Slack Team
 *
 * Copyright (C) 2016, 2017, 2018  Loïc Leuilliot
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

namespace Warlof\Seat\Slackbot\Http\Controllers\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Seat\Web\Http\Controllers\Controller;
use Warlof\Seat\Slackbot\Http\Validation\ValidateOAuth;

class OAuthController extends Controller
{
    /**
     * @param ValidateOAuth $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Seat\Services\Exceptions\SettingException
     */
    public function postConfiguration(ValidateOAuth $request)
    {
        $state = time();

        if (
        (setting('warlof.slackbot.credentials.client_id', true) == $request->input('slack-configuration-client')) &&
        (setting('warlof.slackbot.credentials.client_secret', true) == $request->input('slack-configuration-secret')) &&
        ($request->input('slack-configuration-verification') != '')) {
            setting([
                'warlof.slackbot.credentials.verification_token',
                $request->input('slack-configuration-verification')
            ], true);
            return redirect()->back()->with('success', 'Change has been successfully applied.');
        }

        // store data into the session until OAuth confirmation
        session()->put('warlof.slackbot.credentials', [
            'client_id' => $request->input('slack-configuration-client'),
            'client_secret' => $request->input('slack-configuration-secret'),
            'verification_token' => $request->input('slack-configuration-verification'),
            'state' => $state
        ]);

        return redirect($this->oAuthAuthorization($request->input('slack-configuration-client'), $state));
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        // get back pending OAuth credentials validation from session
        $oauthCredentials = session()->get('warlof.slackbot.credentials');

        session()->forget('warlof.slackbot.credentials');

        // ensure request is legitimate
        if ($oauthCredentials['state'] != $request->input('state')) {
            redirect()->back()
                ->with('error', 'An error occurred while getting back the token. Returned state value is wrong. ' .
                    'In order to prevent any security issue, we stopped transaction.');
        }

        // validating Slack credentials
        try {

            $payload = [
                'client_id' => $oauthCredentials['client_id'],
                'client_secret' => $oauthCredentials['client_secret'],
                'code' => $request->input('code')
            ];

            $response = (new Client())->request('POST', 'https://slack.com/api/oauth.access', [
                'form_params' => $payload
            ]);

            if ($response->getStatusCode() != 200) {
                throw new Exception('Returned status code : ' . $response->getStatusCode() .
                    ' is not matching with 200.');
            }

            $result = json_decode($response->getBody(), true);

            if ($result == null) {
                throw new Exception("response from Slack was empty.");
            }

            if ($result['ok'] == false) {
                throw new Exception($result['error']);
            }

            setting(['warlof.slackbot.credentials.client_id', $oauthCredentials['client_id']], true);
            setting(['warlof.slackbot.credentials.client_secret', $oauthCredentials['client_secret']], true);
            setting(['warlof.slackbot.credentials.access_token', $result['access_token']], true);

            // Used by event API
            if ($oauthCredentials['verification_token'] != null) {
                setting([
                    'warlof.slackbot.credentials.verification_token',
                    $oauthCredentials['verification_token']
                ], true);
            }

        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'An error occurred while trying to confirm OAuth credentials with Slack. ' .
                $e->getMessage());
        }

        return redirect()->route('slackbot.configuration')
            ->with('success', 'The bot credentials has been set.');
    }

    private function oAuthAuthorization($clientId, $state)
    {
        $baseUri = 'https://slack.com/oauth/authorize?';
        $scopes = [
            'channels:read',
            'channels:write',
            'channels:history',
            'groups:read',
            'groups:write',
            'groups:history',
            'users:read',
            'users:read.email'
        ];

        return $baseUri . http_build_query([
            'client_id' => $clientId,
            'scope' => implode(', ', $scopes),
            'state' => $state
        ]);
    }
}
