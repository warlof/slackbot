<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 18:58
 */

namespace Warlof\Seat\Slackbot\Http\Controllers;

use Seat\Web\Http\Controllers\Controller;
use Warlof\Seat\Slackbot\Exceptions\SlackApiException;
use Warlof\Seat\Slackbot\Models\SlackUser;
use Warlof\Seat\Slackbot\Repositories\SlackApi;
use Yajra\Datatables\Facades\Datatables;

class SlackbotController extends Controller
{
    public function getUsers()
    {
        return view('slackbot::users.list');
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

            return redirect()->back()->with('error', sprintf(
                'System cannot find any suitable mapping for Slack (%s).', $slackId));
        }

        return redirect()->back('error', 'An error occurred while processing the request.');
    }

    public function getUsersData()
    {
        $users = SlackUser::whereNull('name')->get();

        if ($users->count()) {
            foreach ($users as $user) {
                try {
                    $member = app(SlackApi::class)->userInfo($user->slack_id);
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

}
