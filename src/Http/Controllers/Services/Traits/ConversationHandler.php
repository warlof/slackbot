<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/12/2016
 * Time: 22:54
 */

namespace Warlof\Seat\Slackbot\Http\Controllers\Services\Traits;


use Illuminate\Http\JsonResponse;
use Warlof\Seat\Slackbot\Exceptions\SlackSettingException;
use Warlof\Seat\Slackbot\Models\SlackChannel;

trait ConversationHandler
{
	use SlackApiConnector;

    private $conversationEvents = [
        'channel_created', 'group_created', 'channel_deleted', 'group_deleted',
        'channel_archive', 'group_archive', 'channel_unarchive', 'group_unarchive',
        'channel_rename', 'group_rename',
    ];

	/**
	 * @param $channel
	 *
	 * @throws SlackSettingException
	 * @throws \Seat\Services\Exceptions\SettingException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
	 */
    private function createConversation($channel)
    {
        // update database information
        SlackChannel::create([
            'id' => $channel['id'],
            'name' => $channel['name'],
            'is_group' => (strpos($channel['id'], 'C') === 0) ? false : true,
            'is_general' => false
        ]);

	    $tokenInfo = $this->getConnector()->invoke('get', '/auth.test');

	    // invite token owner in case he's not the channel creator
	    if ($tokenInfo->user_id != $channel['creator']) {
	    	$this->getConnector()->setBody([
	    		'channel' => $channel['id'],
			    'users' => $tokenInfo->user_id,
		    ])->invoke('post', '/conversations.invite');
	    }
    }

    private function deleteConversation($channelId)
    {
        // update database information
        if ($channel = SlackChannel::find($channelId))
        	$channel->delete();
    }

    private function renameConversation($channel)
    {
        if ($channel = SlackChannel::find($channel['id']))
        	$channel->update([
	            'name' => $channel['name']
	        ]);
    }

    private function archiveConversation($channelId)
    {
        if ($channel = SlackChannel::find($channelId)) {
            $channel->delete();
        }
    }

	/**
	 * @param $channelId
	 *
	 * @throws SlackSettingException
	 * @throws \Seat\Services\Exceptions\SettingException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
	 */
    private function unarchiveConversation($channelId)
    {
	    $channel = $this->getConnector()->setQueryString([
	    	'channel' => $channelId,
	    ])->invoke('get', '/conversations.info');

        // update database information
        SlackChannel::create([
            'id' => $channel->id,
            'name' => $channel->name,
            'is_group' => $channel->is_group,
            'is_general' => false
        ]);
    }

	/**
	 * Business router which is handling Slack conversation event
	 *
	 * @param array $event A Slack Json event object* @param array $event A Slack Json event object
	 *
	 * @return JsonResponse
	 * @throws SlackSettingException
	 * @throws \Seat\Services\Exceptions\SettingException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
	 */
    private function eventConversationHandler(array $event) : JsonResponse
    {
        if (in_array($event['type'], ['channel_created', 'group_created'])) {
            $this->createConversation($event['channel']);
        }

        if (in_array($event['type'], ['channel_deleted', 'group_deleted'])) {
            $this->deleteConversation($event['channel']);
        }

        if (in_array($event['type'], ['channel_archive', 'group_archive'])) {
            $this->archiveConversation($event['channel']);
        }

        if (in_array($event['type'], ['channel_unarchive', 'group_unarchive'])) {
            $this->unarchiveConversation($event['channel']);
        }

        if (in_array($event['type'], ['channel_rename', 'group_rename'])) {
            $this->renameConversation($event['channel']);
        }

        return response()->json(['ok' => true], 200);
    }
}
