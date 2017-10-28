<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 28/10/2017
 * Time: 18:20
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

    public function getLogData()
    {
        $logs = SlackLog::orderBy('created_at', 'desc')->get();

        return Datatables::of($logs)
            ->editColumn('created_at', function($row){
                return view('slackbot::logs.partial.date', compact('row'));
            })
            ->make(true);
    }
}