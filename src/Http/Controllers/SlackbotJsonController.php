<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 28/10/2017
 * Time: 18:11
 */

namespace Warlof\Seat\Slackbot\Http\Controllers;


use Monolog\Logger;
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
use Warlof\Seat\Slackbot\Repositories\Slack\Configuration;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackAuthentication;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackConfiguration;
use Warlof\Seat\Slackbot\Repositories\Slack\SlackApi;

class SlackbotJsonController extends Controller
{

	/**
	 * @param UserChannel $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \Seat\Services\Exceptions\SettingException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
	 */
    public function getJsonUserChannelsData(UserChannel $request)
    {
        $slackId = $request->input('slack_id');

	    if (is_null(setting('warlof.slackbot.credentials.access_token', true)))
	    	return response()->json([]);

	    $configuration = Configuration::getInstance();
	    $configuration->setConfiguration(new SlackConfiguration([
		    'http_user_agent'     => '(Clan Daerie;Warlof Tutsimo;Daerie Inc.;Get Off My Lawn)',
		    'logger_level'        => Logger::DEBUG,
		    'logfile_location'    => storage_path('logs/slack.log'),
		    'file_cache_location' => storage_path('cache/slack/'),
	    ]));

	    $slack = new SlackApi(new SlackAuthentication([
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
	    ]));

	    $conversations_buffer = [];
	    $conversations = $this->fetchConversations($slack);

	    foreach ($conversations as $conversation) {

	    	$members = $this->fetchConversationMembers($slack, $conversation->id);

	    	if (in_array($slackId, $members))
	    		$conversations_buffer[] = [
	    			'id'          => $conversation->id,
				    'name'        => $conversation->name,
	    			'is_channel'  => $conversation->is_channel,
				    'is_group'    => $conversation->is_group,
				    'num_members' => property_exists($conversation, 'num_members') ? $conversation->num_members : 0,
			    ];

	    }

        return response()->json($conversations_buffer);
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
            ->with('error', 'An error occurred while trying to remove the Slack relation for the title.');
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

    //
	// Slack Api
	//

	/**
	 * @param SlackApi $slack
	 * @param string|null $cursor
	 *
	 * @return array
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
	 */
	private function fetchConversations(SlackApi $slack, string $cursor = null)
	{
		$slack->setQueryString([
			'types' => implode(',', ['public_channel', 'private_channel']),
			'exclude_archived' => true,
		]);

		if (!is_null($cursor))
			$slack->setQueryString([
				'cursor' => $cursor,
				'types' => implode(',', ['public_channel', 'private_channel']),
				'exclude_archived' => true,
			]);

		$response = $slack->invoke('get', '/conversations.list');

		$channels = $response->channels;

		if (property_exists($response, 'response_metadata') && $response->response_metadata->next_cursor != '') {
			sleep(1);
			$channels = array_merge(
				$channels,
				$this->fetchConversations($slack, $response->response_metadata->next_cursor)
			);
		}

		return $channels;
	}

	/**
	 * @param SlackApi $slack
	 * @param string $channel_id
	 * @param string|null $cursor
	 *
	 * @return array
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
	 */
	private function fetchConversationMembers(SlackApi $slack, string $channel_id, string $cursor = null) : array
	{
		$slack->setQueryString([
			'channel' => $channel_id,
		]);

		if (!is_null($cursor))
			$slack->setQueryString([
				'channel' => $channel_id,
				'cursor' => $cursor,
			]);

		$response = $slack->invoke( 'get', '/conversations.members' );

		$members = $response->members;

		if (property_exists($response, 'response_metadata') && $response->response_metadata->next_cursor != '') {
			sleep(1);
			$members = array_merge(
				$members,
				$this->fetchConversationMembers($slack, $channel_id, $response->response_metadata->next_cursor));
		}

		return $members;
	}
}
