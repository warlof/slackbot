<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 18:58
 */

namespace Seat\Slackbot\Http\Controllers;

use App\Http\Controllers\Controller;
use Seat\Slackbot\Models\SlackChannelsAlliances;
use Seat\Slackbot\Models\SlackChannelsCorporations;
use Seat\Slackbot\Models\SlackChannelsRoles;
use Seat\Slackbot\Models\SlackChannelsUsers;
use Seat\Slackbot\Validation\AddRelation;

class SlackbotController extends Controller
{

    public function getRelations()
    {
        $channels_users = SlackChannelsUsers::all();
        $channels_roles = SlackChannelsRoles::all();
        $channels_corporations = SlackChannelsCorporations::all();
        $channels_alliances = SlackChannelsAlliances::all();
        
        return view('slackbot::list',
            compact('channels_users', 'channels_roles', 'channels_corporations', 'channels_alliances'));
    }

    public function postRelation(AddRelation $request)
    {
        switch ($request->input('slack-type')) {
            case 'user':
                $relation = new SlackChannelsUsers();
                $relation->user_id = $request->input('slack-user-id');
                $relation->channel_id = $request->input('slack-channel-id');
                $relation->save();

                return redirect()->back()
                    ->with('success', 'New slack user relation has been created');
            case 'role':
                $relation = new SlackChannelsRoles();
                $relation->role_id = $request->input('slack-role-id');
                $relation->channel_id = $request->input('slack-channel-id');
                $relation->save();

                return redirect()->back()
                    ->with('success', 'New slack role relation has been created');
            case 'corporation':
                $relation = new SlackChannelsCorporations();
                $relation->corporation_id = $request->input('slack-corporation-id');
                $relation->channel_id = $request->input('slack-channel-id');
                $relation->save();

                return redirect()->back()
                    ->with('success', 'New slack corporation relation has been created');
            case 'alliance':
                $relation = new SlackChannelsAlliances();
                $relation->alliance_id = $request->input('slack-alliance-id');
                $relation->channel_id = $request->input('slack-channel-id');
                $relation->save();

                return redirect()->back()
                    ->with('success', 'New slack alliance relation has been created');
        }

        return redirect()->back()
            ->with('success', 'No slack relation has been created');
    }

    public function getRemoveUser($user_id, $channel_id)
    {

    }

    public function getRemoveRole($role_id, $channel_id)
    {

    }

    public function getRemoveCorporation($corporation_id, $channel_id)
    {

    }

    public function getRemoveAlliance($alliance_id, $channel_id)
    {
        
    }

}
