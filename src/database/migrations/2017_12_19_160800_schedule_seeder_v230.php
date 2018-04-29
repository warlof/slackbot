<?php
/**
 * This file is part of seat-slackbot and provide user synchronization between both SeAT and a Slack Team
 *
 * Copyright (C) 2016, 2017, 2018  Loïc Leuilliot
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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Seat\Services\Models\Schedule;

class ScheduleSeederV230 extends Migration
{
    protected $schedule = [
        [
            'command'    => 'slack:user:sync',
            'expression' => '*/15 * * * *',
            'allow_overlap'     => false,
            'allow_maintenance' => false,
            'ping_before'       => 'https://slack.com',
            'ping_after'        => null,
        ],
        [
            'command'    => 'slack:user:invite',
            'expression' => '*/10 * * * *',
            'allow_overlap'     => false,
            'allow_maintenance' => false,
            'ping_before'       => 'https://slack.com',
            'ping_after'        => null,
        ],
        [
            'command'    => 'slack:user:kick',
            'expression' => '*/10 * * * *',
            'allow_overlap'     => false,
            'allow_maintenance' => false,
            'ping_before'       => 'https://slack.com',
            'ping_after'        => null,
        ]
    ];

    public function up()
    {
        Schedule::where('command', 'slack:update')->delete();

        foreach ($this->schedule as $job) {
            $existing = Schedule::where('command', $job['command'])
                          ->first();

            if ($existing) {
                $existing->update([
                    'expression' => $job['expression'],
                ]);
            }

            if (!$existing) {
                DB::table('schedules')->insert($job);
            }
        }
    }
}