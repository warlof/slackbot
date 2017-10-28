<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/12/2016
 * Time: 22:54
 */

namespace Warlof\Seat\Slackbot\Http\Controllers\Services\Traits;


use Illuminate\Support\Facades\Redis;
use Seat\Web\Models\User;
use Warlof\Seat\Slackbot\Helpers\Helper;
use Warlof\Seat\Slackbot\Models\SlackUser;
use Warlof\Seat\Slackbot\Repositories\SlackApi;

trait UserHandler
{
    private $userTable = 'seat:warlof:slackbot:users';

    public function userChange($user)
    {
        $channels = [];
        $groups = [];

        if (($seatUser = SlackUser::where('slack_id', $user['id'])->first()) != null) {
            $seatUser->name = $user['name'];
            $seatUser->save();
        }

        if (($data = Redis::get(Helper::getSlackRedisKey($this->userTable, $user['id']))) != null) {
            $data = json_decode($data, true);

            if (key_exists('channels', $data)) {
                $channels = $data['channels'];
            }

            if (key_exists('groups', $data)) {
                $groups = $data['groups'];
            }
        }

        $user['channels'] = $channels;
        $user['groups'] = $groups;

        Redis::set(Helper::getSlackRedisKey($this->userTable, $user['id']), json_encode($user));
    }

    public function joinTeam($user)
    {
        $user = app(SlackApi::class)->getUserInfo($user['id']);

        if (($seatUser = User::where('email', $user['profile']['email'])->first()) != null) {
            SlackUser::create([
                'user_id' => $seatUser->id,
                'slack_id' => $user['id'],
                'name' => $user['name']
            ]);
        }

        $user['conversations'] = [];

        Redis::set(Helper::getSlackRedisKey($this->userTable, $user['id']), json_encode($user));
    }

    public function joinChannel($channel)
    {
        $redisData = Redis::get(Helper::getSlackRedisKey($this->userTable, $channel['user']));

        if ($redisData == null) {
            Redis::set(Helper::getSlackRedisKey($this->userTable, $channel['user']), json_encode(Helper::getSlackUserInformation($channel['user'])));
            return;
        }

        $userInfo = json_decode($redisData, true);
        $userInfo['channels'][] = $channel['channel'];

        Redis::set(Helper::getSlackRedisKey($this->userTable, $channel['user']), json_encode($userInfo));
    }

    public function leaveChannel($channel)
    {
        $redisData = Redis::get(Helper::getSlackRedisKey($this->userTable, $channel['user']));

        if ($redisData == null) {
            Helper::getSlackUserInformation($channel['user']);
            return;
        }

        $userInfo = json_decode($redisData, true);
        $key = array_search($channel['channel'], $userInfo['channels']);

        if ($key !== false) {
            unset($userInfo['channels'][$key]);
        }

        Redis::set(Helper::getSlackRedisKey($this->userTable, $channel['user']), json_encode($userInfo));
    }

    public function joinGroup($group)
    {
        $redisData = Redis::get(Helper::getSlackRedisKey($this->userTable, $group['user']));

        if ($redisData == null) {
            Redis::set(Helper::getSlackRedisKey($this->userTable, $group['user']), json_encode(Helper::getSlackUserInformation($group['user'])));
            return;
        }

        $userInfo = json_decode($redisData, true);
        $userInfo['groups'][] = $group['channel'];

        Redis::set(Helper::getSlackRedisKey($this->userTable, $group['user']), json_encode($userInfo));
    }

    public function leaveGroup($group)
    {
        $redisData = Redis::get(Helper::getSlackRedisKey($this->userTable, $group['user']));

        if ($redisData == null) {
            Helper::getSlackUserInformation($group['user']);
            return;
        }

        $userInfo = json_decode($redisData, true);
        $key = array_search($group['channel'], $userInfo['groups']);

        if ($key !== false) {
            unset($userInfo['groups'][$key]);
        }

        Redis::set(Helper::getSlackRedisKey($this->userTable, $group['user']), json_encode($userInfo));
    }
}