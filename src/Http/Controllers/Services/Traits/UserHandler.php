<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/12/2016
 * Time: 22:54
 */

namespace Warlof\Seat\Slackbot\Http\Controllers\Services\Traits;


use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Seat\Web\Models\User;
use Warlof\Seat\Slackbot\Helpers\Helper;
use Warlof\Seat\Slackbot\Models\SlackUser;
use Warlof\Seat\Slackbot\Repositories\SlackApi;

trait UserHandler
{
    private $userTable = 'seat:warlof:slackbot:users';

    private $userEvents = [
        'user_change', 'team_join',
    ];

    private function userChange($user)
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

        $user['conversations'] = $channels;
        $user['conversations'] = $groups;

        Redis::set(Helper::getSlackRedisKey($this->userTable, $user['id']), json_encode($user));
    }

    private function joinTeam($user)
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

    private function joinChannel($channel)
    {
        $redisData = Redis::get(Helper::getSlackRedisKey($this->userTable, $channel['user']));

        if ($redisData == null) {
            Redis::set(Helper::getSlackRedisKey($this->userTable, $channel['user']),
                json_encode(Helper::getSlackUserInformation($channel['user'])));
            return;
        }

        $userInfo = json_decode($redisData, true);
        $userInfo['conversations'][] = $channel['channel'];

        Redis::set(Helper::getSlackRedisKey($this->userTable, $channel['user']), json_encode($userInfo));
    }

    private function leaveChannel($channel)
    {
        $redisData = Redis::get(Helper::getSlackRedisKey($this->userTable, $channel['user']));

        if ($redisData == null) {
            Helper::getSlackUserInformation($channel['user']);
            return;
        }

        $userInfo = json_decode($redisData, true);
        $key = array_search($channel['channel'], $userInfo['conversations']);

        if ($key !== false) {
            unset($userInfo['conversations'][$key]);
        }

        Redis::set(Helper::getSlackRedisKey($this->userTable, $channel['user']), json_encode($userInfo));
    }

    private function joinGroup($group)
    {
        $redisData = Redis::get(Helper::getSlackRedisKey($this->userTable, $group['user']));

        if ($redisData == null) {
            Redis::set(Helper::getSlackRedisKey($this->userTable, $group['user']),
                json_encode(Helper::getSlackUserInformation($group['user'])));
            return;
        }

        $userInfo = json_decode($redisData, true);
        $userInfo['conversations'][] = $group['channel'];

        Redis::set(Helper::getSlackRedisKey($this->userTable, $group['user']), json_encode($userInfo));
    }

    private function leaveGroup($group)
    {
        $redisData = Redis::get(Helper::getSlackRedisKey($this->userTable, $group['user']));

        if ($redisData == null) {
            Helper::getSlackUserInformation($group['user']);
            return;
        }

        $userInfo = json_decode($redisData, true);
        $key = array_search($group['channel'], $userInfo['conversations']);

        if ($key !== false) {
            unset($userInfo['conversations'][$key]);
        }

        Redis::set(Helper::getSlackRedisKey($this->userTable, $group['user']), json_encode($userInfo));
    }

    /**
     * Business router which is handling Slack user event
     *
     * @param array $event A Slack Json event object
     * @return JsonResponse
     */
    private function eventUserHandler(array $event) : JsonResponse
    {
        switch ($event['type']) {
            case 'user_change':
                $this->userChange($event['user']);
                break;
            case 'team_join':
                $this->joinTeam($event['user']);
                break;
        }

        return response()->json(['ok' => true], 200);
    }

    /**
     * Business router which is handling Slack message event
     *
     * @param array $event A Slack Json event object
     * @return JsonResponse
     */
    private function eventMessageHandler(array $event) : JsonResponse
    {
        $expectedSubEvent = [
            'channel_join',
            'channel_leave',
            'group_join',
            'group_unarchive',
            'group_leave',
            'group_archive',
        ];

        if (!isset($event['subtype'])) {
            return response()->json([
                'ok' => true,
                'msg' => sprintf('Expected %s subtype for message event', implode(', ', $expectedSubEvent))
            ], 202);
        }

        switch ($event['subtype']) {
            case 'channel_join':
                $this->joinChannel($event);
                break;
            case 'channel_leave':
                $this->leaveChannel($event);
                break;
            case 'group_join':
            case 'group_unarchive':
                $this->joinGroup($event);
                break;
            case 'group_leave':
            case 'group_archive':
                $this->leaveGroup($event);
                break;
            default:
                return response()->json([
                    'ok' => true,
                    'msg' => sprintf('Expected %s subtype for message event', implode(', ', $expectedSubEvent))
                ], 202);
        }

        return response()->json(['ok' => true], 200);
    }
}
