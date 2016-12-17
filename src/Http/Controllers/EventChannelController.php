<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 14/12/2016
 * Time: 19:21
 */

namespace Warlof\Seat\Slackbot\Http\Controllers;


use Illuminate\Support\Facades\Redis;
use Warlof\Seat\Slackbot\Helpers\SlackApi;
use Warlof\Seat\Slackbot\Models\SlackChannel;

class EventChannelController
{
    const REDIS_TABLE = 'seat:warlof:slackbot:channels.';

    public static function postChannelCreated($channel)
    {
        // built an unique record key which will be used in order to store and access channel information
        $redisRecordKey = self::REDIS_TABLE . $channel['id'];

        // store channel information into redis
        Redis::set($redisRecordKey, json_encode($channel));

        // update database information
        SlackChannel::create([
            'id' => $channel['id'],
            'name' => $channel['name'],
            'is_group' => (strpos($channel['id'], 'C') === 0) ? false : true,
            'is_general' => false
        ]);
    }

    public static function postChannelDeleted($channelId)
    {
        $redisRecordKey = self::REDIS_TABLE . $channelId;

        // remove information from redis
        Redis::del($redisRecordKey);

        // update database information
        SlackChannel::find($channelId)->delete();
    }

    public function postChannelRename($channel)
    {
        $redisRecordKey = self::REDIS_TABLE . $channel['id'];

        $redisData = json_decode(Redis::get($redisRecordKey), true);

        $redisData['name'] = $channel['name'];
        Redis::set($redisRecordKey, json_encode($redisData));

        SlackChannel::find($channel['id'])->update([
            'name' => $channel['name']
        ]);
    }

    public static function postChannelArchive($channelId)
    {
        $redisRecordKey = self::REDIS_TABLE . $channelId;

        Redis::del($redisRecordKey);

        if ($channel = SlackChannel::find($channelId)) {
            $channel->delete();
        }
    }

    public static function postChannelUnarchive($channelId)
    {
        $redisRecordKey = self::REDIS_TABLE . $channelId;

        $channel = app(SlackApi::class)->info($channelId, false);

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
