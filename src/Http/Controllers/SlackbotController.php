<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 18:58
 */

namespace Warlof\Seat\Slackbot\Http\Controllers;

use Monolog\Logger;
use Seat\Web\Http\Controllers\Controller;
use Warlof\Seat\Slackbot\Models\SlackUser;
use Warlof\Seat\Slackbot\Repositories\Slack\Configuration;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackAuthentication;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackConfiguration;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException;
use Warlof\Seat\Slackbot\Repositories\Slack\SlackApi;
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
    	if (is_null(setting('warlof.slackbot.credentials.access_token', true)))
    		return Datatables::of(collect([]))->make(true);

        $users = SlackUser::whereNull('name')->get();

        if ($users->count() > 0) {

	        $configuration = Configuration::getInstance();
	        $configuration->setConfiguration(new SlackConfiguration([
		        'http_user_agent'     => '(Clan Daerie;Warlof Tutsimo;Daerie Inc.;Get Off My Lawn)',
		        'logger_level'        => Logger::DEBUG,
		        'logfile_location'    => storage_path('logs/slack.log'),
		        'file_cache_location' => storage_path('cache/slack/'),
	        ]));

	        $auth = new SlackAuthentication([
		        'access_token' => setting('warlof.slackbot.credentials.access_token', true),
		        'scopes' => [
			        'users:read',
			        'users:read.email',
			        'channels:read',
			        'channels:write',
			        'groups:read',
			        'groups:write',
			        'im:read',
			        'im:write',
			        'mpim:read',
			        'mpim:write',
			        'read',
			        'post',
		        ],
	        ]);

	        $slack = new SlackApi($auth);

            foreach ($users as $slackUser) {

            	try {
		            $response = $slack->setQueryString([
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
