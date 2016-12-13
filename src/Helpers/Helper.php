<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 13/12/2016
 * Time: 20:33
 */

namespace Warlof\Seat\Slackbot\Helpers;


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Seat\Eveapi\Models\Character\CharacterSheet;
use Seat\Eveapi\Models\Eve\ApiKey;
use Seat\Web\Models\User;
use Warlof\Seat\Slackbot\Models\SlackChannelPublic;
use Warlof\Seat\Slackbot\Models\SlackUser;

class Helper
{
    /**
     * Return true if all API Key are still enable
     *
     * @param Collection $keys
     * @return bool
     */
    public static function isEnabledKey(Collection $keys) : bool
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
     * Determine all channels into which an user is allowed to be
     *
     * @param SlackUser $slackUser
     * @param bool $private
     * @return array
     */
    public static function allowedChannels(SlackUser $slackUser, bool $private) : array
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
}