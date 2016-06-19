<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 18/06/2016
 * Time: 21:01
 */

namespace Seat\Slackbot\Bot;

use Seat\Eveapi\Models\Account\AccountStatus;
use Seat\Eveapi\Models\Character\CharacterSheet;
use Seat\Eveapi\Models\Eve\ApiKey;
use Seat\Services\Models\GlobalSetting;
use Seat\Slackbot\Exceptions\SlackApiException;
use Seat\Slackbot\Exceptions\SlackSettingException;
use Seat\Slackbot\Models\SlackUser;
use Seat\Web\Models\User;

abstract class AbstractSlack
{
    const SLACK_URI_PATTERN = "https://slack.com/api";
    
    protected $slackTokenApi;

    function load()
    {
        // load token and team uri from settings
        $setting = GlobalSetting::where('name', 'slack_token')->first();
        
        if ($setting == null)
            throw new SlackSettingException("missing slack_token");
        
        $this->slackTokenApi = $setting->value;
    }

    /**
     * Return true if all API Key are still enable
     *
     * @param ApiKey $keys[]
     * @return bool
     */
    function isEnabledKey(ApiKey $keys)
    {
        $success = 0;

        foreach ($keys as $key) {
            if ($key->enabled)
                $success++;
        }

        if ($success == count($keys))
            return true;

        return false;
    }

    /**
     * Return true if at least one API Key is still paid until now
     *
     * @param ApiKey $keys[]
     * @return bool
     */
    function isActive(ApiKey $keys)
    {
        foreach ($keys as $key) {
            return (boolean) AccountStatus::where('keyID', $key->key_id)
                ->whereDate('paidUntil', '>=', date('Y-m-d'))
                ->count();
        }

        return false;
    }

    /**
     * Determine if an user has already been invited
     *
     * @param User $user
     * @return bool
     */
    function isInvited(User $user)
    {
        return (boolean) SlackUser::where('user_id', $user->id)
            ->where('invited', true)
            ->count();
    }

    /**
     * Determine all channels in which an user is allowed to be
     *
     * @param SlackUser $slackUser
     * @return array
     */
    function allowedChannels(SlackUser $slackUser)
    {
        $channels = [];

        $rows = User::join('slack_channels_users', 'slack_channels_users.user_id', 'users.id')
            ->select('channel_id')
            ->where('users.id', $slackUser->slack_id)
            ->union(
                RoleUser::join('slack_channels_roles', 'slack_channels_roles.role_id', 'role_user.role_id')
                    ->where('role_user.user_id', $slackUser->slack_id)
                    ->select('channel_id')
                    ->get()
            )->union(
                ApiKey::join('account_api_key_info_characters', 'account_api_key_info_characters.keyID', 'eve_api_keys.key_id')
                    ->join('slack_channels_corporations', 'slack_channels_corporations.corporation_id', 'account_api_key_info_characters.corporationID')
                    ->where('eve_api_keys.user_id', $slackUser->slack_id)
                    ->select('channel_id')
                    ->get()
            )->union(
                CharacterSheet::join('slack_channels_alliances', 'slack_channels_alliances.alliance_id', 'character_character_sheets.allianceID')
                    ->join('account_api_key_info_characters', 'account_api_key_info_characters.characterID', 'character_character_sheets.characterID')
                    ->join('eve_api_keys', 'eve_api_keys.key_id', 'account_api_key_info_characters.keyID')
                    ->where('eve_api_keys.user_id', $slackUser->slack_id)
                    ->select('channel_id')
                    ->get()
            )->get();

        foreach ($rows as $row) {
            $channels[] = $row->channel_id;
        }

        return $channels;
    }

    function processSlackApiPost($endpoint, $parameters = [])
    {
        // add slack token to the post parameters
        $parameters['token'] = $this->slackTokenApi;

        // prepare curl request using passed parameters and endpoint
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, AbstractSlack::SLACK_URI_PATTERN . $endpoint);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($parameters));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = json_decode(curl_exec($curl));

        if ($result == null) {
            throw new SlackApiException("An error occurred while calling the Slack API\r\n" . curl_error($curl));
        }
        
        return $result;
    }
}