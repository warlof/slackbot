<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 18:51
 */

namespace Seat\Slackbot\Commands;


use Illuminate\Console\Command;
use PhpSlackBot\Bot;
use Seat\Services\Settings\Seat;
use Seat\Slackbot\Events\SlackEventHandler;
use Seat\Slackbot\Exceptions\SlackSettingException;

class SlackDaemon extends Command
{
    protected $signature = 'slack:daemon:run';

    protected $description = 'Slack service which handle Slack event. Mandatory in order to keep slack user and channels up to date.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $token = Seat::get('slack_token');

        if ($token == null)
            throw new SlackSettingException("missing slack_token in settings");

        $bot = new Bot();
        $bot->setToken($token);

        // catch all event and return them to SlackTeamJoin
        $bot->loadCatchAllCommand(new SlackEventHandler());
        $bot->run();

        return;
    }
}
