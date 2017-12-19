<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 18:58
 */

namespace Warlof\Seat\Slackbot\Http\Controllers;

use Seat\Web\Http\Controllers\Controller;
use Warlof\Seat\Slackbot\Http\Controllers\Services\Traits\SlackApiConnector;
use Warlof\Seat\Slackbot\Models\SlackUser;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException;
use Yajra\Datatables\Facades\Datatables;

class SlackbotController extends Controller
{
    use SlackApiConnector;

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

    /**
     * @return mixed
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Exceptions\SlackSettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    public function getUsersData()
    {
        if (is_null(setting('warlof.slackbot.credentials.access_token', true)))
            return Datatables::of(collect([]))->make(true);

        $users = SlackUser::whereNull('name')->get();

        if ($users->count() > 0) {

            foreach ($users as $slackUser) {

                try {
                    $response = $this->getConnector()->setQueryString([
                        'email' => $slackUser->user->email,
                    ])->invoke( 'get', '/users.lookupByEmail' );
                    $slackUser->update( [
                        'name' => property_exists( $response->user, 'name' ) ? $response->user->name : '',
                    ] );

                    if ( $users->count() > 1 ) {
                        sleep( 1 );
                    }
                } catch (RequestFailedException $e) {

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
