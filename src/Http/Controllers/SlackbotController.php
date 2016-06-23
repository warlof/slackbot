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
        $channel_users = SlackChannelUser::all();
        $channel_roles = SlackChannelRole::all();
        $channel_corporations = SlackChannelCorporation::all();
        $channel_alliances = SlackChannelAlliance::all();

        return view('slackbot::list',
            compact('channel_users', 'channel_roles', 'channel_corporations', 'channel_alliances'));
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
