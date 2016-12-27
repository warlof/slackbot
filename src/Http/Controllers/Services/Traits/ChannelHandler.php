<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/12/2016
 * Time: 22:54
 */

namespace Warlof\Seat\Slackbot\Http\Controllers\Services\Traits;


use Illuminate\Support\Facades\Redis;
use Warlof\Seat\Slackbot\Models\SlackChannel;

trait ChannelHandler
{
    private $channelTable = 'seat:warlof:slackbot:channels.';

    public function createChannel($channel)
    {
        // built an unique record key which will be used in order to store and access channel information
        $redisRecordKey = $this->channelTable . $channel['id'];

        // store channel information into redis
        Redis::set($redisRecordKey, json_encode($channel));

        // update database information
        SlackChannel::create([
            'id' => $channel['id'],
            'name' => $channel['name'],
            'is_group' => false,
            'is_general' => false
        ]);
    }

    public function deleteChannel($channelId)
    {
        $redisRecordKey = $this->channelTable . $channelId;

        // remove information from redis
        Redis::del($redisRecordKey);

        // update database information
        SlackChannel::find($channelId)->delete();
    }

    public function renameChannel($channel)
    {
        $redisRecordKey = $this->channelTable . $channel['id'];

        $redisData = json_decode(Redis::get($redisRecordKey), true);

        $redisData['name'] = $channel['name'];
        Redis::set($redisRecordKey, json_encode($redisData));

        SlackChannel::find($channel['id'])->update([
            'name' => $channel['name']
        ]);
    }

    public function archiveChannel($channelId)
    {
        $redisRecordKey = $this->channelTable . $channelId;

        Redis::del($redisRecordKey);

        if ($channel = SlackChannel::find($channelId)) {
            $channel->delete();
        }
    }

    public function unarchiveChannel($channelId)
    {
        $redisRecordKey = $this->channelTable . $channelId;

        $channel = app('Warlof\Seat\Slackbot\Repositories\SlackApi')->info($channelId, false);

        Redis::set($redisRecordKey, json_encode($channel));

        // update database information
        SlackChannel::create([
            'id' => $channel['id'],
            'name' => $channel['name'],
            'is_group' => (strpos($channel['id'], 'C') === 0) ? false : true,
            'is_general' => false
        ]);
    }
}