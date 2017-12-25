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
use Warlof\Seat\Slackbot\Models\CorporationTitle;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Models\SlackChannelAlliance;
use Warlof\Seat\Slackbot\Models\SlackChannelCorporation;
use Warlof\Seat\Slackbot\Models\SlackChannelPublic;
use Warlof\Seat\Slackbot\Models\SlackChannelRole;
use Warlof\Seat\Slackbot\Models\SlackChannelTitle;
use Warlof\Seat\Slackbot\Models\SlackChannelUser;
use Warlof\Seat\Slackbot\Models\SlackFilter;
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
	    $channelUsers = SlackFilter::where('related_type', User::class)->get();
	    $channelRoles = SlackFilter::where('related_type', Role::class)->get();
        $channelCorporations = SlackFilter::where('related_type', CorporationSheet::class)->get();
        $channelTitles = SlackFilter::where('related_type', CorporationTitle::class)->get();
        $channelAlliances = SlackFilter::where('related_type', AllianceList::class)->get();

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

    public function getRemoveRelation($relatedType, $channelId, $relatedId = null)
    {
    	if (is_null(SlackChannel::find($channelId)))
    		return redirect()->back()
		                     ->with('error', sprintf('Unable to retrieve any channel with ID %s !', $channelId));

    	if ($relatedType == 'public')
    		return $this->getRemovePublic($channelId);

    	if (!in_array($relatedType, ['user', 'role', 'corporation', 'alliance', 'title']))
    		return redirect()->back()->with('error', 'Unknown relation type.');

    	switch ($relatedType) {
		    case 'user':
		    	$relatedType = User::class;
		    	break;
		    case 'role':
		    	$relatedType = Role::class;
		    	break;
		    case 'corporation':
		    	$relatedType = CorporationSheet::class;
		    	break;
		    case 'alliance':
		    	$relatedType = AllianceList::class;
		    	break;
		    case 'title':
		    	$relatedType = CorporationTitle::class;
		    	break;
	    }

    	if (is_null(call_user_func(array($relatedType, 'find'), $relatedId)))
    		return redirect()->back()
		                     ->with('error', sprintf('Unable to retrieve any entity with ID %s !', $relatedId));

    	$filter = SlackFilter::where('related_type', $relatedType)
	                         ->where('related_id', $relatedId)
	                         ->where('channel_id', $channelId);

    	if ($filter->count() < 1)
    		return redirect()->back()->with('error', 'This relation does not exist !');

    	SlackFilter::where('related_type', $relatedType)
	               ->where('related_id', $relatedId)
	               ->where('channel_id', $channelId)
	               ->delete();

	    return redirect()->back()->with('success', 'Slack filter has been created.');
    }

    //
    // Grant access
    //

    public function postRelation(AddRelation $request)
    {
        $channelId = $request->input('slack-channel-id');

	    if (is_null(SlackChannel::find($channelId)))
		    return redirect()->back()
		                     ->with('error', sprintf('Unable to retrieve any channel with ID %s !', $channelId));

        // use a single post route in order to create any kind of relation
        // value are user, role, corporation or alliance
        if ($request->input('slack-type') == 'public')
        	return $this->postPublicRelation($channelId);

        $related = $this->getRelatedType($request);

        if (count($related) < 2)
	        return redirect()->back()->with('error', 'Unknown relation type.');

        if (is_null(call_user_func(array($related['type'], 'find'), $related['id'])))
	        return redirect()->back()
	                         ->with('error', sprintf('Unable to retrieve any entity with ID %s !', $related['id']));

        $filter = SlackFilter::where('related_type', $related['type'])
                             ->where('related_id', $related['id'])
                             ->where('channel_id', $channelId);

        if ($filter->count() > 0)
	        return redirect()->back()->with('error', 'This relation already exists !');

	    SlackFilter::create([
		    'related_type' => $related['type'],
		    'related_id'   => $related['id'],
		    'channel_id'   => $channelId,
	    ]);

	    return redirect()->back()->with('success', 'New slack filter has been created.');
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

    private function getRelatedType(AddRelation $request)
    {
	    // use a single post route in order to create any kind of relation
	    // value are user, role, corporation or alliance
	    switch ($request->input('slack-type')) {
		    case 'user':
		    	return [
		    		'type' => User::class,
				    'id'   => $request->input('slack-user-id'),
			    ];
		    case 'role':
			    return [
			    	'type' => Role::class,
				    'id'   => $request->input('slack-role-id'),
			    ];
		    case 'corporation':
			    return [
			    	'type' => CorporationSheet::class,
				    'id'   => $request->input('slack-corporation-id'),
			    ];
		    case 'title':
			    return [
			    	'type' => CorporationTitle::class,
				    'id'   => $request->input('slack-title-id'),
			    ];
		    case 'alliance':
		    	return [
		    		'type' => AllianceList::class,
				    'id'   => $request->input('slack-alliance-id'),
			    ];
	    }

	    return [];
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
