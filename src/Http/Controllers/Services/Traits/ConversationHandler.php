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
use Warlof\Seat\Slackbot\Repositories\Slack\Configuration;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackAuthentication;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackConfiguration;
use Warlof\Seat\Slackbot\Repositories\Slack\SlackApi;

trait ConversationHandler
{
    private $conversationEvents = [
        'channel_created', 'group_created', 'channel_deleted', 'group_deleted',
        'channel_archive', 'group_archive', 'channel_unarchive', 'group_unarchive',
        'channel_rename', 'group_rename',
    ];

    private function createConversation($channel)
    {
        // update database information
        SlackChannel::create([
            'id' => $channel['id'],
            'name' => $channel['name'],
            'is_group' => (strpos($channel['id'], 'C') === 0) ? false : true,
            'is_general' => false
        ]);

	    $configuration = Configuration::getInstance();
	    $configuration->setConfiguration(new SlackConfiguration([
		    'http_user_agent'     => '(Clan Daerie;Warlof Tutsimo;Daerie Inc.;Get Off My Lawn)',
		    'logger_level'        => Logger::DEBUG,
		    'logfile_location'    => storage_path('logs/slack.log'),
		    'file_cache_location' => storage_path('cache/slack/'),
	    ]));

	    if (is_null(setting('warlof.slackbot.credentials.access_token', true)))
		    throw new SlackSettingException("warlof.slackbot.credentials.access_token is missing in settings. " .
		                                    "Ensure you've link SeAT to a valid Slack Team.");

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

	    $tokenInfo = $slack->invoke('get', '/auth.test');

	    // invite token owner in case he's not the channel creator
	    if ($tokenInfo->user_id != $channel['creator']) {
	    	$slack->setBody([
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

    private function unarchiveConversation($channelId)
    {
	    $configuration = Configuration::getInstance();
	    $configuration->setConfiguration(new SlackConfiguration([
		    'http_user_agent'     => '(Clan Daerie;Warlof Tutsimo;Daerie Inc.;Get Off My Lawn)',
		    'logger_level'        => Logger::DEBUG,
		    'logfile_location'    => storage_path('logs/slack.log'),
		    'file_cache_location' => storage_path('cache/slack/'),
	    ]));

	    if (is_null(setting('warlof.slackbot.credentials.access_token', true)))
		    throw new SlackSettingException("warlof.slackbot.credentials.access_token is missing in settings. " .
		                                    "Ensure you've link SeAT to a valid Slack Team.");

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

	    $channel = $slack->setQueryString([
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
     * @param array $event A Slack Json event object
     * @return JsonResponse
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
