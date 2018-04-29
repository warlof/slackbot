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

namespace Warlof\Seat\Slackbot\Helpers;


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Seat\Eveapi\Models\Character\CharacterInfo;
use Seat\Eveapi\Models\RefreshToken;
use Seat\Web\Models\Group;
use Warlof\Seat\Slackbot\Models\SlackChannelPublic;
use Warlof\Seat\Slackbot\Models\SlackUser;

class Helper
{
    /**
     * Return true if account is active
     *
     * An account is considered as active when both mail has been confirmed in case of mail activation,
     * and no administrator disabled it
     *
     * @param Group $group
     * @return bool
     */
    public static function isEnabledAccount(Group $group) : bool
    {
        return ($group->users->count() == $group->users->where('active', true)->count());
    }

    /**
     * Return true if all API Key are still enable
     *
     * @param Collection $characterIDs
     * @return bool
     */
    public static function isEnabledKey(Collection $characterIDs) : bool
    {
        // retrieve all token which are matching with the user IDs list
        $tokens = RefreshToken::whereIn('character_id', $characterIDs->toArray());

        // compare both list
        // if tokens amount is matching with characters list - return true
        return ($characterIDs->count() == $tokens->count());
    }

    /**
     * Determine all channels into which an user is allowed to be
     *
     * @param SlackUser $slackUser
     * @return array
     */
    public static function allowedChannels(SlackUser $slackUser) : array
    {
        $channels = [];

        if (!Helper::isEnabledAccount($slackUser->group))
            return $channels;

        if (!Helper::isEnabledKey($slackUser->group->users->first()->associatedCharacterIds()))
            return $channels;

        $rows = Group::join('slack_channel_users', 'slack_channel_users.group_id', '=', 'groups.id')
                    ->join('slack_channels', 'slack_channel_users.channel_id', '=', 'slack_channels.id')
                    ->select('channel_id')
                    ->where('groups.id', $slackUser->group_id)
                    ->where('slack_channels.is_general', (int) false)
                    ->union(
                        // fix model declaration calling the table directly
                        DB::table('role_user')->join('slack_channel_roles', 'slack_channel_roles.role_id', '=',
                            'role_user.role_id')
                          ->join('slack_channels', 'slack_channel_roles.channel_id', '=', 'slack_channels.id')
                          ->whereIn('role_user.user_id', $slackUser->group->users->first()->associatedCharacterIds())
                          ->where('slack_channels.is_general', (int) false)
                          ->select('channel_id')
                    )->union(
                CharacterInfo::join('slack_channel_corporations', 'slack_channel_corporations.corporation_id', '=',
                          'character_infos.corporation_id')
                      ->join('slack_channels', 'slack_channel_corporations.channel_id', '=', 'slack_channels.id')
                      ->whereIn('character_infos.character_id', $slackUser->group->users->first()->associatedCharacterIds())
                      ->where('slack_channels.is_general', (int) false)
                      ->select('channel_id')
            )->union(
                CharacterInfo::join('character_titles', 'character_infos.character_id', '=', 'character_titles.character_id')
                             ->join('slack_channel_titles', function ($join) {
                                 $join->on('slack_channel_titles.corporation_id', '=',
                                     'character_infos.corporation_id');
                                 $join->on('slack_channel_titles.title_id', '=',
                                     'character_titles.title_id');
                             })
                             ->join('slack_channels', 'slack_channel_titles.channel_id', '=', 'slack_channels.id')
                             ->whereIn('character_infos.character_id', $slackUser->group->users->first()->associatedCharacterIds())
                             ->where('slack_channels.is_general', (int) false)
                             ->select('channel_id')
            )->union(
                CharacterInfo::join('slack_channel_alliances', 'slack_channel_alliances.alliance_id', '=',
                    'character_infos.alliance_id')
                              ->join('slack_channels', 'slack_channel_alliances.channel_id', '=', 'slack_channels.id')
                              ->whereIn('character_infos.character_id', $slackUser->group->users->first()->associatedCharacterIds())
                              ->where('slack_channels.is_general', (int) false)
                              ->select('channel_id')
            )->union(
                SlackChannelPublic::join('slack_channels', 'slack_channel_public.channel_id', '=', 'slack_channels.id')
                                  ->where('slack_channels.is_general', (int) false)
                                  ->select('channel_id')
            )->get();

        $channels = $rows->unique('channel_id')->pluck('channel_id')->toArray();

        return $channels;
    }

    public static function getSlackRedisKey($table, $objectId)
    {
        return $table . '.' . $objectId;
    }
}
