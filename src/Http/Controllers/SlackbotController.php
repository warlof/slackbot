<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 18:58
 */

namespace Warlof\Seat\Slackbot\Http\Controllers;

use GuzzleHttp\Client;
use Guzzle\Http\Exception\RequestException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;
use Seat\Eveapi\Models\Corporation\Title;
use Seat\Web\Http\Controllers\Controller;
use Seat\Web\Models\User;
use Seat\Web\Models\Acl\Role;
use Seat\Eveapi\Models\Corporation\CorporationSheet;
use Seat\Eveapi\Models\Eve\AllianceList;
use Warlof\Seat\Slackbot\Exceptions\SlackApiException;
use Warlof\Seat\Slackbot\Http\Validation\UserChannel;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Models\SlackChannelPublic;
use Warlof\Seat\Slackbot\Models\SlackChannelTitle;
use Warlof\Seat\Slackbot\Models\SlackChannelUser;
use Warlof\Seat\Slackbot\Models\SlackChannelRole;
use Warlof\Seat\Slackbot\Models\SlackChannelCorporation;
use Warlof\Seat\Slackbot\Models\SlackChannelAlliance;
use Warlof\Seat\Slackbot\Models\SlackLog;
use Warlof\Seat\Slackbot\Http\Validation\AddRelation;
use Warlof\Seat\Slackbot\Models\SlackUser;
use Yajra\Datatables\Facades\Datatables;

class SlackbotController extends Controller
{
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

    public function getUsers()
    {
        return view('slackbot::users.list');
    }

    public function getConfiguration()
    {
        $changelog = $this->getChangelog();
        
        return view('slackbot::configuration', compact('changelog'));
    }
    
    public function getLogs()
    {
        $logCount = SlackLog::count();
        return view('slackbot::logs.list', compact('logCount'));
    }

    public function getLogData()
    {
        $logs = SlackLog::orderBy('created_at', 'desc')->get();

        return Datatables::of($logs)
            ->editColumn('created_at', function($row){
                return view('slackbot::logs.partial.date', compact('row'));
            })
            ->make(true);
    }

    public function postRemoveUserMapping()
    {
        $slackId = request()->input('slack_id');

        if ($slackId != '') {

            if (($slackUser = SlackUser::where('slack_id', $slackId)->first()) != null) {
                $slackUser->delete();

                return redirect()->back()->with('success', 'System successfully remove the mapping between SeAT (' .
                    $slackUser->user->name . ') and Slack (' . $slackUser->name . ').');
            }

            return redirect()->back()->with('error', 'System cannot find any suitable mapping for Slack (' . $slackId . ').');
        }

        return redirect()->back('error', 'An error occurred while processing the request.');
    }

    public function getUsersData()
    {
        $users = SlackUser::whereNull('name')->get();

        if ($users->count()) {
            foreach ($users as $user) {
                try {
                    $member = app('Warlof\Seat\Slackbot\Repositories\SlackApi')->userInfo($user->slack_id);
                    $user->update([
                        'name' => $member['name']
                    ]);
                } catch (SlackApiException $e) {

                }
            }
        }

        $users = SlackUser::all();

        return Datatables::of($users)
            ->addColumn('user_id', function($row){
                return $row->user_id;
            })
            ->addColumn('user_name', function($row){
                return $row->user->name;
            })
            ->addColumn('slack_id', function($row){
                return $row->slack_id;
            })
            ->addColumn('slack_name', function($row){
                return $row->name;
            })
            ->make(true);
    }

    public function getJsonUserChannelsData(UserChannel $request)
    {
        $slackId = $request->input('slack_id');

        if (($member = json_decode(Redis::get('seat:warlof:slackbot:users.' . $slackId), true)) == null) {
            $member = app('Warlof\Seat\Slackbot\Repositories\SlackApi')->userInfo($slackId);
            $member['channels'] = app('Warlof\Seat\Slackbot\Repositories\SlackApi')->memberOf($slackId, false);
            $member['groups'] = app('Warlof\Seat\Slackbot\Repositories\SlackApi')->memberOf($slackId, true);

            Redis::set('seat:warlof:slackbot:users.' . $slackId, json_encode($member));
        }

        $channels = [];
        foreach ($member['channels'] as $channelId) {
            if (($channel = json_decode(Redis::get('seat:warlof:slackbot:channels.' . $channelId), true)) == null) {
                $channel = app('Warlof\Seat\Slackbot\Repositories\SlackApi')->info($channelId, false);

                Redis::set('seat:warlof:slackbot:channels.' . $channelId, json_encode($channel));
            }

            $channels[] = [$channel['id'], $channel['name'], count($channel['members'])];
        }

        $groups = [];
        foreach ($member['groups'] as $groupId) {
            if (($group = json_decode(Redis::get('seat:warlof:slackbot:groups.' . $groupId), true)) == null) {
                $group = app('Warlof\Seat\Slackbot\Repositories\SlackApi')->info($groupId, true);

                Redis::set('seat:warlof:slackbot:groups.' . $groupId, json_encode($group));
            }

            $groups[] = [$group['id'], $group['name'], count($group['members'])];
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
    }

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

    public function getSubmitJob($commandName)
    {
        $acceptedCommands = [
            'slack:channels:update',
            'slack:users:update',
            'slack:logs:clear'
        ];
        
        if (!in_array($commandName, $acceptedCommands)) {
            abort(401);
        }

        Artisan::call($commandName);

        return redirect()->back()
            ->with('success', 'The command has been run.');
    }

    private function getChangelog() : string
    {
        try {
            $response = (new Client())
                ->request('GET', "https://raw.githubusercontent.com/warlof/slackbot/master/CHANGELOG.md");

            if ($response->getStatusCode() != 200) {
                return 'Error while fetching changelog';
            }

            $parser = new \Parsedown();
            return $parser->parse($response->getBody());
        } catch (RequestException $e) {
            return 'Error while fetching changelog';
        }
    }

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
