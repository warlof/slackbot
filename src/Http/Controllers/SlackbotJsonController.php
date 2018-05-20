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

namespace Warlof\Seat\Slackbot\Http\Controllers;

use Exception;
use Seat\Eveapi\Models\Alliances\Alliance;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Seat\Eveapi\Models\Corporation\CorporationTitle;
use Seat\Web\Http\Controllers\Controller;
use Seat\Web\Models\Acl\Role;
use Seat\Web\Models\Group;
use Warlof\Seat\Slackbot\Http\Controllers\Services\Traits\SlackApiConnector;
use Warlof\Seat\Slackbot\Http\Validation\AddRelation;
use Warlof\Seat\Slackbot\Http\Validation\UserChannel;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Models\SlackChannelAlliance;
use Warlof\Seat\Slackbot\Models\SlackChannelCorporation;
use Warlof\Seat\Slackbot\Models\SlackChannelPublic;
use Warlof\Seat\Slackbot\Models\SlackChannelRole;
use Warlof\Seat\Slackbot\Models\SlackChannelTitle;
use Warlof\Seat\Slackbot\Models\SlackChannelUser;

class SlackbotJsonController extends Controller
{

	use SlackApiConnector;

    /**
     * @param UserChannel $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Seat\Services\Exceptions\SettingException
     */
    public function getJsonUserChannelsData(UserChannel $request)
    {
        $slackId = $request->input('slack_id');

        if (is_null(setting('warlof.slackbot.credentials.access_token', true)))
            return response()->json([]);

        try {
	        $conversations_buffer = $this->fetchUserConversations($slackId);
	        return response()->json($conversations_buffer);
        } catch (Exception $e) {
        	return response()->json($e->getMessage(), 500);
        }
    }

    public function getJsonTitle()
    {
        $corporationId = request()->input('corporation_id');

        if (!empty($corporationId)) {
            $titles = CorporationTitle::where('corporation_id', $corporationId)->select('title_id', 'name')
                ->get();

            return response()->json($titles->map(
                function($item){
                    return [
                        'title_id' => $item->title_id,
                        'name' => strip_tags($item->name)
                    ];
                })
            );
        }

        return response()->json([]);
    }

    public function getRelations()
    {
        $channelPublic = SlackChannelPublic::all();
        $channelGroups = SlackChannelUser::all();
        $channelRoles = SlackChannelRole::all();
        $channelCorporations = SlackChannelCorporation::all();
        $channelTitles = SlackChannelTitle::all();
        $channelAlliances = SlackChannelAlliance::all();

        $groups = Group::all();
        $roles = Role::orderBy('title')->get();
        $corporations = CorporationInfo::orderBy('name')->get();
        $alliances = Alliance::orderBy('name')->get();
        $channels = SlackChannel::orderBy('name')->get();

        return view('slackbot::access.list',
            compact('channelPublic', 'channelGroups', 'channelRoles', 'channelCorporations', 'channelTitles',
                'channelAlliances', 'groups', 'roles', 'corporations', 'alliances', 'channels'));
    }

    //
    // Remove access
    //

    public function getRemovePublic($channelId)
    {
        $channelPublic = SlackChannelPublic::where('channel_id', $channelId);

        if ($channelPublic != null) {
            $channelPublic->delete();
            return redirect()->back()
                ->with('success', 'The public slack relation has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the public Slack relation.');
    }

    public function getRemoveUser($group_id, $channel_id)
    {
        $channelUser = SlackChannelUser::where('group_id', $group_id)
            ->where('channel_id', $channel_id);

        if ($channelUser != null) {
            $channelUser->delete();
            return redirect()->back()
                ->with('success', 'The slack relation for the user has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Slack relation for the user.');
    }

    public function getRemoveRole($roleId, $channelId)
    {
        $channelRole = SlackChannelRole::where('role_id', $roleId)
            ->where('channel_id', $channelId);

        if ($channelRole != null) {
            $channelRole->delete();
            return redirect()->back()
                ->with('success', 'The slack relation for the role has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Slack relation for the role.');
    }

    public function getRemoveCorporation($corporationId, $channelId)
    {
        $channelCorporation = SlackChannelCorporation::where('corporation_id', $corporationId)
            ->where('channel_id', $channelId);

        if ($channelCorporation != null) {
            $channelCorporation->delete();
            return redirect()->back()
                ->with('success', 'The slack relation for the corporation has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Slack relation for the corporation.');
    }

    public function getRemoveTitle($corporationId, $titleId, $channelId)
    {
        $channelTitle = SlackChannelTitle::where('corporation_id', $corporationId)
            ->where('title_id', $titleId)
            ->where('channel_id', $channelId);

        if ($channelTitle != null) {
            $channelTitle->delete();
            return redirect()->back()
                ->with('success', 'The slack relation for the title has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurred while trying to remove the Slack relation for the title.');
    }

    public function getRemoveAlliance($allianceId, $channelId)
    {
        $channelAlliance = SlackChannelAlliance::where('alliance_id', $allianceId)
            ->where('channel_id', $channelId);

        if ($channelAlliance != null) {
            $channelAlliance->delete();
            return redirect()->back()
                ->with('success', 'The slack relation for the alliance has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Slack relation for the alliance.');
    }

    //
    // Grant access
    //

    public function postRelation(AddRelation $request)
    {
        $groupId = $request->input('slack-group-id');
        $roleId = $request->input('slack-role-id');
        $corporationId = $request->input('slack-corporation-id');
        $titleId = $request->input('slack-title-id');
        $allianceId = $request->input('slack-alliance-id');
        $channelId = $request->input('slack-channel-id');

        // use a single post route in order to create any kind of relation
        // value are user, role, corporation or alliance
        switch ($request->input('slack-type')) {
            case 'public':
                return $this->postPublicRelation($channelId);
            case 'user':
                return $this->postGroupRelation($channelId, $groupId);
            case 'role':
                return $this->postRoleRelation($channelId, $roleId);
            case 'corporation':
                return $this->postCorporationRelation($channelId, $corporationId);
            case 'title':
                return $this->postTitleRelation($channelId, $corporationId, $titleId);
            case 'alliance':
                return $this->postAllianceRelation($channelId, $allianceId);
            default:
                return redirect()->back()
                    ->with('error', 'Unknown relation type');
        }
    }

    //
    // Helper methods
    //

    private function postPublicRelation($channelId)
    {
        if (SlackChannelPublic::find($channelId) == null) {
            SlackChannelPublic::create([
                'channel_id' => $channelId,
                'enable' => true
            ]);

            return redirect()->back()
                ->with('success', 'New public slack relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }

    private function postGroupRelation($channelId, $groupId)
    {
        $relation = SlackChannelUser::where('channel_id', '=', $channelId)
            ->where('group_id', '=', $groupId)
            ->get();

        if ($relation->count() == 0) {
            SlackChannelUser::create([
                'group_id' => $groupId,
                'channel_id' => $channelId,
                'enable' => true
            ]);

            return redirect()->back()
                ->with('success', 'New slack user relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }

    private function postRoleRelation($channelId, $roleId)
    {
        $relation = SlackChannelRole::where('role_id', '=', $roleId)
            ->where('channel_id', '=', $channelId)
            ->get();

        if ($relation->count() == 0) {
            SlackChannelRole::create([
                'role_id' => $roleId,
                'channel_id' => $channelId,
                'enable' => true
            ]);

            return redirect()->back()
                ->with('success', 'New slack role relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }

    private function postCorporationRelation($channelId, $corporationId)
    {
        $relation = SlackChannelCorporation::where('corporation_id', '=', $corporationId)
            ->where('channel_id', '=', $channelId)
            ->get();

        if ($relation->count() == 0) {
            SlackChannelCorporation::create([
                'corporation_id' => $corporationId,
                'channel_id' => $channelId,
                'enable' => true
            ]);

            return redirect()->back()
                ->with('success', 'New slack corporation relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }

    private function postTitleRelation($channelId, $corporationId, $titleId)
    {
        $relation = SlackChannelTitle::where('corporation_id', '=', $corporationId)
            ->where('title_id', '=', $titleId)
            ->where('channel_id', '=', $channelId)
            ->get();

        if ($relation->count() == 0) {
            SlackChannelTitle::create([
                'corporation_id' => $corporationId,
                'title_id' => $titleId,
                'channel_id' => $channelId,
                'enable' => true
            ]);

            return redirect()->back()
                ->with('success', 'New slack title relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }

    private function postAllianceRelation($channelId, $allianceId)
    {
        $relation = SlackChannelAlliance::where('alliance_id', '=', $allianceId)
            ->where('channel_id', '=', $channelId)
            ->get();

        if ($relation->count() == 0) {
            SlackChannelAlliance::create([
                'alliance_id' => $allianceId,
                'channel_id' => $channelId,
                'enable' => true
            ]);

            return redirect()->back()
                ->with('success', 'New slack alliance relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }
}
