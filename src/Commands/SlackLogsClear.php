<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 18:51
 */

namespace Seat\Slackbot\Commands;


use Illuminate\Console\Command;
use Seat\Services\Settings\Seat;
use Seat\Slackbot\Exceptions\SlackSettingException;
use Seat\Slackbot\Helpers\SlackApi;
use Seat\Slackbot\Models\SlackChannel;
use Seat\Slackbot\Models\SlackLog;

class SlackLogsClear extends Command
{
    protected $signature = 'slack:logs:clear';

    protected $description = 'Clearing slack logs';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        SlackLog::truncate();
    }
}
