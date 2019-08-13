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

namespace Warlof\Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Eveapi\Models\Character\CharacterInfo;
use Seat\Web\Models\Group;

class SlackUser extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'group_id', 'slack_id', 'name'
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'group_id';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'id');
    }

    /**
     * Determine if all user tokens are actives or not.
     *
     * @return bool
     */
    public function isEnabledKey(): bool
    {
        return $this->group->refresh_tokens->count() === $this->group->refresh_tokens()->withTrashed()->count();
    }

    /**
     * Return true if account is active.
     *
     * An account is considered as active when both mail has been confirmed in case of mail activation,
     * and no administrator disabled it.
     *
     * @return bool
     */
    public function isEnabledAccount(): bool
    {
        return $this->group->users->count() === $this->group->users->where('active', true)->count();
    }

    /**
     * Determine if the user can be in the channel sent in parameter.
     *
     * @param string $channel_id
     * @return bool
     */
    public function isAllowedChannel(string $channel_id): bool
    {
        if (! $this->isEnabledAccount())
            return false;

        if (! $this->isEnabledKey())
            return false;

        return in_array($channel_id, $this->allowedChannels());
    }

    /**
     * @return array
     */
    public function allowedChannels(): array
    {
        $rows = $this->getChannelsUserBased(false)
            ->union($this->getChannelsRoleBased(false))
            ->union($this->getChannelsCorporationBased(false))
            ->union($this->getChannelsCorporationTitleBased(false))
            ->union($this->getChannelsAllianceBased(false))
            ->union($this->getChannelsPublicBased(false))
            ->get();

        return $rows->unique('channel_id')->pluck('channel_id')->toArray();
    }

    /**
     * Return all channels ID related to user mapping matching to the user.
     *
     * @param bool $get
     * @return mixed
     */
    public function getChannelsUserBased(bool $get)
    {
        $channels = SlackChannelUser::join('groups', 'slack_channel_users.group_id', '=', 'groups.id')
            ->join('slack_channels', 'slack_channel_users.channel_id', '=', 'slack_channels.id')
            ->where('groups.id', $this->group_id)
            ->where('slack_channels.is_general', false)
            ->select('channel_id');

        return $get ? $channels->get() : $channels;
    }

    /**
     * Return all channels ID related to role mapping matching to the user.
     *
     * @param bool $get
     * @return mixed
     */
    public function getChannelsRoleBased(bool $get)
    {
        $channels = SlackChannelRole::join('group_role', 'slack_channel_roles.role_id', '=', 'group_role.role_id')
            ->join('slack_channels', 'slack_channel_roles.channel_id', '=', 'slack_channels.id')
            ->where('group_role.group_id', $this->group_id)
            ->where('slack_channels.is_general', false)
            ->select('channel_id');

        return $get ? $channels->get() : $channels;
    }

    /**
     * Return all channels ID related to corporation mapping matching to the user.
     *
     * @param bool $get
     * @return mixed
     */
    public function getChannelsCorporationBased(bool $get)
    {
        $channels = SlackChannelCorporation::join('character_infos',
              'slack_channel_corporations.corporation_id', '=', 'character_infos.corporation_id')
            ->join('slack_channels', 'slack_channel_corporations.channel_id', '=', 'slack_channels.id')
            ->whereIn('character_infos.character_id', $this->group->users->pluck('id')->toArray())
            ->where('slack_channels.is_general', false)
            ->select('channel_id');

        return $get ? $channels->get() : $channels;
    }

    /**
     * Return all channels ID related to title mapping matching to the user.
     *
     * @param bool $get
     * @return mixed
     */
    public function getChannelsCorporationTitleBased(bool $get)
    {
        $channels = CharacterInfo::join('character_info_corporation_title',
              'character_infos.character_id', '=', 'character_info_corporation_title.character_info_character_id')
            ->join('corporation_titles', 'character_info_corporation_title.corporation_title_id', '=', 'corporation_titles.id')
            ->join('slack_channel_titles', function ($join) {
                $join->on('slack_channel_titles.title_id', '=', 'corporation_titles.title_id');
                $join->on('slack_channel_titles.corporation_id', '=', 'corporation_titles.corporation_id');
            })
            ->join('slack_channels', 'slack_channel_titles.channel_id', '=', 'slack_channels.id')
            ->whereIn('character_infos.character_id', $this->group->users->pluck('id')->toArray())
            ->where('slack_channels.is_general', false)
            ->select('channel_id');

        return $get ? $channels->get() : $channels;
    }

    /**
     * Return all channels ID related to alliance mapping matching to the user.
     *
     * @param bool $get
     * @return mixed
     */
    public function getChannelsAllianceBased(bool $get)
    {
        $channels = SlackChannelAlliance::join('character_infos',
              'slack_channel_alliances.alliance_id', '=', 'character_infos.alliance_id')
            ->join('slack_channels', 'slack_channel_alliances.channel_id', '=', 'slack_channels.id')
            ->whereIn('character_infos.character_id', $this->group->pluck('id')->toArray())
            ->where('slack_channels.is_general', false)
            ->select('channel_id');

        return $get ? $channels->get() : $channels;
    }

    /**
     * Return all channels ID related to public mapping
     *
     * @param bool $get
     * @return mixed
     */
    public function getChannelsPublicBased(bool $get)
    {
        $channels = SlackChannelPublic::join('slack_channels', 'slack_channel_public.channel_id', '=', 'slack_channels.id')
            ->where('slack_channels.is_general', false)
            ->select('channel_id');

        return $get ? $channels->get() : $channels;
    }
}
