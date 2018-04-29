<?php
/**
 * This file is part of slackbot and provide user synchronization between both SeAT and a Slack Team
 *
 * Copyright (C) 2016, 2017, 2018  Loïc Leuilliot <loic.leuilliot@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
