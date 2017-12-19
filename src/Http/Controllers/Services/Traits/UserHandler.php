<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/12/2016
 * Time: 22:54
 */

namespace Warlof\Seat\Slackbot\Http\Controllers\Services\Traits;


use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

trait UserHandler
{
    private $userEvents = [
        'user_change', 'team_join',
    ];

    private function joinTeam()
    {
        Artisan::call('slack:user:sync');
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
            case 'team_join':
                $this->joinTeam();
                break;
        }

        return response()->json(['ok' => true], 200);
    }

    /**
     * Business router which is handling Slack message event
     *
     * @return JsonResponse
     */
    private function eventMessageHandler() : JsonResponse
    {
        return response()->json([
            'ok' => true,
            'msg' => 'Deprecated endpoint. Will be remove in 2.4.0',
        ], 202);
    }
}
