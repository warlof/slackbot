<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 28/10/2017
 * Time: 18:11
 */

namespace Warlof\Seat\Slackbot\Http\Controllers;


use Illuminate\Support\Facades\Redis;
use Seat\Eveapi\Models\Corporation\CorporationSheet;
use Seat\Eveapi\Models\Corporation\Title;
use Seat\Eveapi\Models\Eve\AllianceList;
use Seat\Web\Http\Controllers\Controller;
use Seat\Web\Models\Acl\Role;
use Seat\Web\Models\User;
use Warlof\Seat\Slackbot\Http\Validation\AddRelation;
use Warlof\Seat\Slackbot\Http\Validation\UserChannel;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Models\SlackChannelAlliance;
use Warlof\Seat\Slackbot\Models\SlackChannelCorporation;
use Warlof\Seat\Slackbot\Models\SlackChannelPublic;
use Warlof\Seat\Slackbot\Models\SlackChannelRole;
use Warlof\Seat\Slackbot\Models\SlackChannelTitle;
use Warlof\Seat\Slackbot\Models\SlackChannelUser;
use Warlof\Seat\Slackbot\Repositories\SlackApi;

class SlackbotJsonController extends Controller
{
    public function getJsonUserChannelsData(UserChannel $request)
    {
        $slackId = $request->input('slack_id');

        if (($member = json_decode(Redis::get('seat:warlof:slackbot:users.' . $slackId), true)) == null) {
            $member = app(SlackApi::class)->userInfo($slackId);
            $member['conversations'] = app(SlackApi::class)->memberOf($slackId);

            Redis::set('seat:warlof:slackbot:users.' . $slackId, json_encode($member));
        }

        $groups = [];
        $channels = [];

        foreach ($member['conversations'] as $channelId) {
            $stats = json_decode(Redis::get('seat:warlof:slackbot:stats.conversations.' . $channelId), true);

            if (is_null($stats)) {
                $channel = app(SlackApi::class)->info($channelId);
                $members = app(SlackApi::class)->channelsMembers($channelId);
                $stats = [
                    $channelId,
                    $channel['name'],
                    count($members),
                ];

                Redis::set('seat:warlof:slackbot:stats.conversations.' . $channelId, json_encode($stats));
            }

            switch (strpos($channelId, 'C')) {
                // conversation is a public channel
                case 0:
                    $channels[] = $stats;
                    break;
                // conversation is a private group
                default:
                    $groups[] = $stats;
            }
        }

        return response()->json(['channels' => $channels, 'groups' => $groups]);
    }

    public function getJsonTitle()
    {
        $corporationId = request()->input('corporation_id');

        if (!empty($corporationId)) {
            $titles = Title::where('corporationID', $corporationId)->select('titleID', 'titleName')
                ->get();

            return response()->json($titles->map(
                function($item){
                    return [
                        'titleID' => $item->titleID,
                        'titleName' => strip_tags($item->titleName)
                    ];
                })
            );
        }

        return response()->json([]);
    }

    public function getRelations()
    {
        $channelPublic = SlackChannelPublic::all();
        $channelUsers = SlackChannelUser::all();
        $channelRoles = SlackChannelRole::all();
        $channelCorporations = SlackChannelCorporation::all();
        $channelTitles = SlackChannelTitle::all();
        $channelAlliances = SlackChannelAlliance::all();

        $users = User::all();
        $roles = Role::all();
        $corporations = CorporationSheet::all();
        $alliances = AllianceList::all();
        $channels = SlackChannel::all();

        return view('slackbot::access.list',
            compact('channelPublic', 'channelUsers', 'channelRoles', 'channelCorporations', 'channelTitles',
                'channelAlliances', 'users', 'roles', 'corporations', 'alliances', 'channels'));
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

    public function getRemoveUser($userId, $channelId)
    {
        $channelUser = SlackChannelUser::where('user_id', $userId)
            ->where('channel_id', $channelId);

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
            ->with('error', 'An error occures while trying to remove the Slack relation for the title.');
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
        $userId = $request->input('slack-user-id');
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
                return $this->postUserRelation($channelId, $userId);
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

    private function postUserRelation($channelId, $userId)
    {
        $relation = SlackChannelUser::where('channel_id', '=', $channelId)
            ->where('user_id', '=', $userId)
            ->get();

        if ($relation->count() == 0) {
            SlackChannelUser::create([
                'user_id' => $userId,
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
