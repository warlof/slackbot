<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 18/06/2016
 * Time: 21:01
 */

namespace Seat\Slackbot\Jobs;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Seat\Eveapi\Models\Account\AccountStatus;
use Seat\Eveapi\Models\Character\CharacterSheet;
use Seat\Eveapi\Models\Eve\ApiKey;
use Seat\Services\Settings\Seat;
use Seat\Slackbot\Exceptions\SlackSettingException;
use Seat\Slackbot\Helpers\SlackApi;
use Seat\Slackbot\Models\SlackChannelPublic;
use Seat\Slackbot\Models\SlackLog;
use Seat\Slackbot\Models\SlackUser;
use Seat\Web\Models\User;

abstract class AbstractSlack
{
    /**
     * @var User the user we're checking access
     */
    protected $user;

    /**
     * @var SlackApi The Slack Token API
     */
    private $slackApi;

    /**
     * Set the Slack token API
     *
     * @throws \Seat\Slackbot\Exceptions\SlackSettingException
     */
    public function call()
    {
        // load token and team uri from settings
        $token = Seat::get('slack_token');

        if ($token == null) {
            throw new SlackSettingException("missing slack_token in settings");
        }

        $this->slackApi = new SlackApi($token);
    }

    /**
     * Enable to affect an User object to the current Job
     *
     * @param User $user
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        
        return $this;
    }

    /**
     * @return SlackApi
     */
    protected function getSlackApi()
    {
        return $this->slackApi;
    }

    /**
     * Return true if all API Key are still enable
     *
     * @param Collection $keys
     * @return bool
     */
    protected function isEnabledKey(Collection $keys)
    {
        // count keys with enable value and compare it to total keys number
        $enabledKeys = $keys->filter(function($item){
            return $item->enabled == 1;
        })->count();
        
        if ($enabledKeys == $keys->count() && $keys->count() != 0) {
            return true;
        }

        return false;
    }

    /**
     * Return true if at least one account is still paid until now
     *
     * @param Collection $keys
     * @return bool
     */
    protected function isActive(Collection $keys)
    {
        // iterate over keys and compare the paidUntil field value to current date
        foreach ($keys as $key) {
            if (AccountStatus::where('keyID', $key->key_id)
                ->whereDate('paidUntil', '>=', date('Y-m-d'))
                ->count() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if an user has already been invited
     *
     * @param User $user
     * @return bool
     */
    protected function isInvited(User $user)
    {
        return (boolean) SlackUser::where('user_id', $user->id)
            ->where('invited', true)
            ->count();
    }

    /**
     * Determine all channels in which an user is allowed to be
     *
     * @param SlackUser $slackUser
     * @param boolean $private Determine if channels should be private (group) or public (channel)
     * @return array
     */
    protected function allowedChannels(SlackUser $slackUser, $private)
    {
        $channels = [];

        $rows = User::join('slack_channel_users', 'slack_channel_users.user_id', '=', 'users.id')
            ->join('slack_channels', 'slack_channel_users.channel_id', '=', 'slack_channels.id')
            ->select('channel_id')
            ->where('users.id', $slackUser->user_id)
            ->where('slack_channels.is_group', (int) $private)
            ->where('slack_channels.is_general', (int) false)
            ->union(
                // fix model declaration calling the table directly
                DB::table('role_user')->join('slack_channel_roles', 'slack_channel_roles.role_id', '=',
                    'role_user.role_id')
                    ->join('slack_channels', 'slack_channel_roles.channel_id', '=', 'slack_channels.id')
                    ->where('role_user.user_id', $slackUser->user_id)
                    ->where('slack_channels.is_group', (int) $private)
                    ->where('slack_channels.is_general', (int) false)
                    ->select('channel_id')
            )->union(
                ApiKey::join('account_api_key_info_characters', 'account_api_key_info_characters.keyID', '=',
                    'eve_api_keys.key_id')
                    ->join('slack_channel_corporations', 'slack_channel_corporations.corporation_id', '=',
                        'account_api_key_info_characters.corporationID')
                    ->join('slack_channels', 'slack_channel_corporations.channel_id', '=', 'slack_channels.id')
                    ->where('eve_api_keys.user_id', $slackUser->user_id)
                    ->where('slack_channels.is_group', (int) $private)
                    ->where('slack_channels.is_general', (int) false)
                    ->select('channel_id')
            )->union(
                CharacterSheet::join('slack_channel_alliances', 'slack_channel_alliances.alliance_id', '=',
                    'character_character_sheets.allianceID')
                    ->join('slack_channels', 'slack_channel_alliances.channel_id', '=', 'slack_channels.id')
                    ->join('account_api_key_info_characters', 'account_api_key_info_characters.characterID', '=',
                        'character_character_sheets.characterID')
                    ->join('eve_api_keys', 'eve_api_keys.key_id', '=', 'account_api_key_info_characters.keyID')
                    ->where('eve_api_keys.user_id', $slackUser->user_id)
                    ->where('slack_channels.is_group', (int) $private)
                    ->where('slack_channels.is_general', (int) false)
                    ->select('channel_id')
            )->union(
                SlackChannelPublic::join('slack_channels', 'slack_channel_public.channel_id', '=', 'slack_channels.id')
                    ->where('slack_channels.is_group', (int) $private)
                    ->where('slack_channels.is_general', (int) false)
                    ->select('channel_id')
            )->get();

        foreach ($rows as $row) {
            $channels[] = $row->channel_id;
        }

        return $channels;
    }

    protected function logEvent($eventType, $channels = null)
    {
        $message = '';
        $channelsString = '';

        if ($channels != null) {
            $channelsString = implode(',', $channels);
        }

        switch ($eventType)
        {
            case 'invite':
                $message = 'The user ' . $this->user->name . ' has been invited to following channels : ' .
                    $channelsString;
                break;
            case 'kick':
                $message = 'The user ' . $this->user->name . ' has been kicked from following channels : ' .
                    $channelsString;
                break;
            case 'mail':
                $message = 'The mail address for user ' . $this->user->name . ' has not been set (' .
                    $this->user->email . ')';
                break;
        }

        SlackLog::create([
            'event' => $eventType,
            'message' => $message
        ]);
    }
}
