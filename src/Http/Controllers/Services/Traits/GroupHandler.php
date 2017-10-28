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

trait GroupHandler
{
    private $groupTable = 'seat:warlof:slackbot:conversations';

    public function createGroup($group)
    {
        // store channel information into redis
        Redis::set(Helper::getSlackRedisKey($this->groupTable, $group['id']), json_encode($group));

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
        // remove information from redis
        Redis::del(Helper::getSlackRedisKey($this->groupTable, $groupId));

        // update database information
        SlackChannel::find($groupId)->delete();
    }

    public function renameGroup($group)
    {
        $redisData = json_decode(Redis::get(Helper::getSlackRedisKey($this->groupTable, $group['id'])), true);

        $redisData['name'] = $group['name'];

        Redis::set(Helper::getSlackRedisKey($this->groupTable, $group['id']), json_encode($redisData));

        SlackChannel::find($group['id'])->update([
            'name' => $group['name']
        ]);
    }

    public function archiveGroup($groupId)
    {
        Redis::del(Helper::getSlackRedisKey($this->groupTable, $groupId));

        if ($channel = SlackChannel::find($groupId)) {
            $channel->delete();
        }
    }

    public function unarchiveGroup($groupId)
    {
        $channel = app(SlackApi::class)->info($groupId);

        Redis::set(Helper::getSlackRedisKey($this->groupTable, $groupId), json_encode($channel));

        // update database information
        SlackChannel::create([
            'id' => $channel['id'],
            'name' => $channel['name'],
            'is_group' => (strpos($channel['id'], 'C') === 0) ? false : true,
            'is_general' => false
        ]);
    }
}