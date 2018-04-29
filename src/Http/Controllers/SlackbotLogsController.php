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

namespace Warlof\Seat\Slackbot\Http\Controllers;

use Seat\Web\Http\Controllers\Controller;
use Warlof\Seat\Slackbot\Models\SlackLog;
use Yajra\Datatables\Facades\Datatables;

class SlackbotLogsController extends Controller
{
    public function getLogs()
    {
        $logCount = SlackLog::count();
        return view('slackbot::logs.list', compact('logCount'));
    }

    public function getJsonLogData()
    {
        $logs = SlackLog::orderBy('created_at', 'desc')->get();

        return Datatables::of($logs)
            ->editColumn('created_at', function($row){
                return view('slackbot::logs.partial.date', compact('row'));
            })
            ->make(true);
    }
}
