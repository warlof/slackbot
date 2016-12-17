<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/12/2016
 * Time: 22:54
 */

namespace Warlof\Seat\Slackbot\Http\Controllers\Services\Traits;


use Illuminate\Support\Facades\Redis;
use Seat\Web\Models\User;
use Warlof\Seat\Slackbot\Models\SlackUser;

trait UserHandler
{
    private $redisTable = 'seat:warlof:slackbot:users.';

    public function joinTeam($user)
    {
        $redisRecordKey = $this->redisTable . $user['id'];

        if (($seatUser = User::where('email', $user['profile']['email'])->first()) != null) {
            SlackUser::create([
                'user_id' => $seatUser->id,
                'slack_id' => $user['id']
            ]);
        }

        Redis::set($redisRecordKey, json_encode($user));
    }

    public function joinChannel($channel)
    {
        $redisRecordKey = $this->redisTable . $channel['user'];

        $redisData = Redis::get($redisRecordKey);

        if ($redisData == null) {
            $userInfo = app('warlof.slackbot.slack')->userInfo($channel['user']);
            $userInfo['channels'] = [];
            $userInfo['groups'] = [];
        } else {
            $userInfo = json_decode($redisData, true);
        }

        $userInfo['channels'][] = $channel['channel'];

        Redis::set($redisRecordKey, json_encode($userInfo));
    }

    public function leaveChannel($channel)
    {
        $redisRecordKey = $this->redisTable . $channel['user'];

        $redisData = Redis::get($redisRecordKey);

        if ($redisData == null) {
            return;
        }

        $userInfo = json_decode($redisData, true);
        $key = array_search($channel['channel'], $userInfo['channels']);

        if ($key !== false) {
            unset($userInfo['channels'][$key]);
        }

        Redis::set($redisRecordKey, json_encode($userInfo));
    }

    public function joinGroup($group)
    {
        $redisRecordKey = $this->redisTable . $group['user'];

        $redisData = Redis::get($redisRecordKey);

        if ($redisData == null) {
            $userInfo = ['channels' => [], 'groups' => []];
        } else {
            $userInfo = json_decode($redisData, true);
        }

        $userInfo['groups'][] = $group['channel'];

        Redis::set($redisRecordKey, json_encode($userInfo));
    }

    public function leaveGroup($group)
    {
        $redisRecordKey = $this->redisTable . $group['user'];

        $redisData = Redis::get($redisRecordKey);

        if ($redisData == null)
            return;

        $userInfo = json_decode($redisData, true);
        $key = array_search($group['channel'], $userInfo['groups']);

        if ($key !== false) {
            unset($userInfo['groups'][$key]);
        }

        Redis::set($redisRecordKey, json_encode($userInfo));
    }
}