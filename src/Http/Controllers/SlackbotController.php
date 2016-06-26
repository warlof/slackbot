<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 18:58
 */

namespace Seat\Slackbot\Http\Controllers;

use App\Http\Controllers\Controller;
use Seat\Slackbot\Models\SlackChannelUser;
use Seat\Slackbot\Models\SlackChannelRole;
use Seat\Slackbot\Models\SlackChannelCorporation;
use Seat\Slackbot\Models\SlackChannelAlliance;
use Seat\Slackbot\Validation\AddRelation;

class SlackbotController extends Controller
{

    public function getRelations()
    {
        $channelUsers = SlackChannelUser::all();
        $channelRoles = SlackChannelRole::all();
        $channelCorporations = SlackChannelCorporation::all();
        $channelAlliances = SlackChannelAlliance::all();

        return view('slackbot::list',
            compact('channelUsers', 'channelRoles', 'channelCorporations', 'channelAlliances'));
    }

    public function postRelation(AddRelation $request)
    {
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
        }

        return redirect()->back()
            ->with('success', 'No slack relation has been created');
    }

    public function getRemoveUser($userId, $channelId)
    {

    }

    public function getRemoveRole($roleId, $channelId)
    {

    }

    public function getRemoveCorporation($corporationId, $channelId)
    {

    }

    public function getRemoveAlliance($allianceId, $channelId)
    {
        
    }

}
