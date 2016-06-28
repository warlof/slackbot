<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 18:58
 */

namespace Seat\Slackbot\Http\Controllers;

use App\Http\Controllers\Controller;
use Seat\Eveapi\Models\Corporation\CorporationSheet;
use Seat\Services\Settings\Seat;
use Seat\Slackbot\Models\SlackChannel;
use Seat\Slackbot\Models\SlackChannelUser;
use Seat\Slackbot\Models\SlackChannelRole;
use Seat\Slackbot\Models\SlackChannelCorporation;
use Seat\Slackbot\Models\SlackChannelAlliance;
use Seat\Slackbot\Validation\AddRelation;
use Seat\Slackbot\Validation\ValidateConfiguration;
use Seat\Web\Models\Acl\Role;
use Seat\Web\Models\User;

class SlackbotController extends Controller
{

    public function getRelations()
    {
        $channelUsers = SlackChannelUser::all();
        $channelRoles = SlackChannelRole::all();
        $channelCorporations = SlackChannelCorporation::all();
        $channelAlliances = SlackChannelAlliance::all();

        $users = User::all();
        $roles = Role::all();
        $corporations = CorporationSheet::all();
        $alliances = $corporations->unique('allianceID');
        $channels = SlackChannel::all();

        return view('slackbot::list',
            compact('channelUsers', 'channelRoles', 'channelCorporations', 'channelAlliances',
                'users', 'roles', 'corporations', 'alliances', 'channels'));
    }

    public function getConfiguration()
    {
        $token = Seat::get('slack_token');
        
        return view('slackbot::configuration', compact('token'));
    }

    public function postRelation(AddRelation $request)
    {
        // use a single post route in order to create any kind of relation
        // value are user, role, corporation or alliance
        switch ($request->input('slack-type')) {
            case 'user':
                $relation = new SlackChannelUser();
                $relation->user_id = $request->input('slack-user-id');
                $relation->channel_id = $request->input('slack-channel-id');
                $relation->save();

                return redirect()->back()
                    ->with('success', 'New slack user relation has been created');
            case 'role':
                $relation = new SlackChannelRole();
                $relation->role_id = $request->input('slack-role-id');
                $relation->channel_id = $request->input('slack-channel-id');
                $relation->save();

                return redirect()->back()
                    ->with('success', 'New slack role relation has been created');
            case 'corporation':
                $relation = new SlackChannelCorporation();
                $relation->corporation_id = $request->input('slack-corporation-id');
                $relation->channel_id = $request->input('slack-channel-id');
                $relation->save();

                return redirect()->back()
                    ->with('success', 'New slack corporation relation has been created');
            case 'alliance':
                $relation = new SlackChannelAlliance();
                $relation->alliance_id = $request->input('slack-alliance-id');
                $relation->channel_id = $request->input('slack-channel-id');
                $relation->save();

                return redirect()->back()
                    ->with('success', 'New slack alliance relation has been created');
            default:
                return redirect()->back()
                    ->with('error', 'Unknown relation type');
        }
    }
    
    public function postConfiguration(ValidateConfiguration $request)
    {
        Seat::set('slack_token', $request->input('slack-configuration-token'));

        return redirect()->back()
            ->with('success', 'Slackbot setting has been updated.');
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

}
