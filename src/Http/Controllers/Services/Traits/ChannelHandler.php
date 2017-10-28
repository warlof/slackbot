<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/12/2016
 * Time: 22:54
 */

namespace Warlof\Seat\Slackbot\Http\Controllers\Services\Traits;


use Illuminate\Support\Facades\Redis;
use Warlof\Seat\Slackbot\Helpers\Helper;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Repositories\SlackApi;

trait ChannelHandler
{
    private $channelTable = 'seat:warlof:slackbot:conversations';

    public function createChannel($channel)
    {
        // store channel information into redis
        Redis::set(Helper::getSlackRedisKey($this->channelTable, $channel['id']), json_encode($channel));

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
        // remove information from redis
        Redis::del(Helper::getSlackRedisKey($this->channelTable, $channelId));

        // update database information
        SlackChannel::find($channelId)->delete();
    }

    public function renameChannel($channel)
    {
        $redisData = json_decode(Redis::get(Helper::getSlackRedisKey($this->channelTable, $channel['id'])), true);

        $redisData['name'] = $channel['name'];

        Redis::set(Helper::getSlackRedisKey($this->channelTable, $channel['id']), json_encode($redisData));

        SlackChannel::find($channel['id'])->update([
            'name' => $channel['name']
        ]);
    }

    public function archiveChannel($channelId)
    {
        Redis::del(Helper::getSlackRedisKey($this->channelTable, $channelId));

        if ($channel = SlackChannel::find($channelId)) {
            $channel->delete();
        }
    }

    public function unarchiveChannel($channelId)
    {
        $channel = app(SlackApi::class)->info($channelId);

        Redis::set(Helper::getSlackRedisKey($this->channelTable, $channelId), json_encode($channel));

        // update database information
        SlackChannel::create([
            'id' => $channel['id'],
            'name' => $channel['name'],
            'is_group' => (strpos($channel['id'], 'C') === 0) ? false : true,
            'is_general' => false
        ]);
    }
}