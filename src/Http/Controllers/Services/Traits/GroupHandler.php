<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/12/2016
 * Time: 22:54
 */

namespace Warlof\Seat\Slackbot\Http\Controllers\Services\Traits;


use Illuminate\Support\Facades\Redis;
use Warlof\Seat\Slackbot\Models\SlackChannel;

trait GroupHandler
{
    private $groupTable = 'seat:warlof:slackbot:groups.';

    public function createGroup($group)
    {
        // built an unique record key which will be used in order to store and access channel information
        $redisRecordKey = $this->groupTable . $group['id'];

        // store channel information into redis
        Redis::set($redisRecordKey, json_encode($group));

        // update database information
        SlackChannel::create([
            'id' => $group['id'],
            'name' => $group['name'],
            'is_group' => true,
            'is_general' => false
        ]);
    }

    public function deleteGroup($groupId)
    {
        $redisRecordKey = $this->groupTable . $groupId;

        // remove information from redis
        Redis::del($redisRecordKey);

        // update database information
        SlackChannel::find($groupId)->delete();
    }

    public function renameGroup($group)
    {
        $redisRecordKey = $this->groupTable . $group['id'];

        $redisData = json_decode(Redis::get($redisRecordKey), true);

        $redisData['name'] = $group['name'];
        Redis::set($redisRecordKey, json_encode($redisData));

        SlackChannel::find($group['id'])->update([
            'name' => $group['name']
        ]);
    }

    public function archiveGroup($groupId)
    {
        $redisRecordKey = $this->groupTable . $groupId;

        Redis::del($redisRecordKey);

        if ($channel = SlackChannel::find($groupId)) {
            $channel->delete();
        }
    }

    public function unarchiveGroup($groupId)
    {
        $redisRecordKey = $this->groupTable . $groupId;

        $channel = app('warlof.slackbot.slack')->info($groupId, true);

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