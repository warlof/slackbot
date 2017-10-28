<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/12/2016
 * Time: 22:54
 */

namespace Warlof\Seat\Slackbot\Http\Controllers\Services\Traits;


use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Warlof\Seat\Slackbot\Helpers\Helper;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Repositories\SlackApi;

trait ConversationHandler
{
    private $conversationTable = 'seat:warlof:slackbot:conversations';

    private $conversationEvents = [
        'channel_created', 'group_created', 'channel_deleted', 'group_deleted',
        'channel_archive', 'group_archive', 'channel_unarchive', 'group_unarchive',
        'channel_rename', 'group_rename',
    ];

    private function createConversation($channel)
    {
        // store channel information into redis
        Redis::set(Helper::getSlackRedisKey($this->conversationTable, $channel['id']), json_encode($channel));

        // update database information
        SlackChannel::create([
            'id' => $channel['id'],
            'name' => $channel['name'],
            'is_group' => (strpos($channel['id'], 'C') === 0) ? false : true,
            'is_general' => false
        ]);

        // invite token owner in case he's not the channel creator
        app(SlackApi::class)->joinConversation($channel['id']);
    }

    private function deleteConversation($channelId)
    {
        // remove information from redis
        Redis::del(Helper::getSlackRedisKey($this->conversationTable, $channelId));

        // update database information
        SlackChannel::find($channelId)->delete();
    }

    private function renameConversation($channel)
    {
        $redisData = json_decode(Redis::get(Helper::getSlackRedisKey($this->conversationTable, $channel['id'])), true);

        $redisData['name'] = $channel['name'];

        Redis::set(Helper::getSlackRedisKey($this->conversationTable, $channel['id']), json_encode($redisData));

        SlackChannel::find($channel['id'])->update([
            'name' => $channel['name']
        ]);
    }

    private function archiveConversation($channelId)
    {
        Redis::del(Helper::getSlackRedisKey($this->conversationTable, $channelId));

        if ($channel = SlackChannel::find($channelId)) {
            $channel->delete();
        }
    }

    private function unarchiveConversation($channelId)
    {
        $channel = app(SlackApi::class)->getConversationInfo($channelId);

        Redis::set(Helper::getSlackRedisKey($this->conversationTable, $channelId), json_encode($channel));

        // update database information
        SlackChannel::create([
            'id' => $channel['id'],
            'name' => $channel['name'],
            'is_group' => (strpos($channel['id'], 'C') === 0) ? false : true,
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
