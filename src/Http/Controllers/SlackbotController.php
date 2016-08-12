<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 18:58
 */

namespace Seat\Slackbot\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Seat\Eveapi\Models\Corporation\CorporationSheet;
use Seat\Eveapi\Models\Eve\AllianceList;
use Seat\Services\Settings\Seat;
use Seat\Slackbot\Models\SlackChannel;
use Seat\Slackbot\Models\SlackChannelPublic;
use Seat\Slackbot\Models\SlackChannelUser;
use Seat\Slackbot\Models\SlackChannelRole;
use Seat\Slackbot\Models\SlackChannelCorporation;
use Seat\Slackbot\Models\SlackChannelAlliance;
use Seat\Slackbot\Models\SlackLog;
use Seat\Slackbot\Validation\AddRelation;
use Seat\Slackbot\Validation\ValidateConfiguration;
use Seat\Web\Models\Acl\Role;
use Seat\Web\Models\User;

class SlackbotController extends Controller
{
    public function getRelations()
    {
        $channelPublic = SlackChannelPublic::all();
        $channelUsers = SlackChannelUser::all();
        $channelRoles = SlackChannelRole::all();
        $channelCorporations = SlackChannelCorporation::all();
        $channelAlliances = SlackChannelAlliance::all();
        
        $users = User::all();
        $roles = Role::all();
        $corporations = CorporationSheet::all();
        $alliances = AllianceList::all();
        $channels = SlackChannel::all();

        return view('slackbot::list',
            compact('channelPublic', 'channelUsers', 'channelRoles', 'channelCorporations', 'channelAlliances',
                'users', 'roles', 'corporations', 'alliances', 'channels'));
    }

    public function getConfiguration()
    {
        $token = Seat::get('slack_token');

        $parser = new \Parsedown();
        $changelog = $parser->parse($this->getChangelog());
        
        return view('slackbot::configuration', compact('token', 'changelog'));
    }
    
    public function getLogs()
    {
        $logs = SlackLog::orderBy('created_at', 'desc')->take(30)->get();

        return view('slackbot::logs', compact('logs'));
    }

    public function postRelation(AddRelation $request)
    {
        $userId = $request->input('slack-user-id');
        $roleId = $request->input('slack-role-id');
        $corporationId = $request->input('slack-corporation-id');
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
            case 'alliance':
                return $this->postAllianceRelation($channelId, $allianceId);
            default:
                return redirect()->back()
                    ->with('error', 'Unknown relation type');
        }
    }

    public function postConfiguration(ValidateConfiguration $request)
    {
        Seat::set('slack_token', $request->input('slack-configuration-token'));
        return redirect()->back()
            ->with('success', 'The Slack test token has been updated');
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

    private function getChangelog()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://raw.githubusercontent.com/warlof/slackbot/master/CHANGELOG.md");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        return curl_exec($curl);
    }

    private function postPublicRelation($channelId)
    {
        if (SlackChannelPublic::find($channelId) == null) {
            SlackChannelPublic::create([
                'channel_id' => $channelId
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
                'channel_id' => $channelId]);

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
                'channel_id' => $channelId
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
                'channel_id' => $channelId
            ]);

            return redirect()->back()
                ->with('success', 'New slack corporation relation has been created');
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
                'channel_id' => $channelId
            ]);

            return redirect()->back()
                ->with('success', 'New slack alliance relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }

}
