<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 18:51
 */

namespace Seat\Slackbot\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpSlackBot\Bot;
use Seat\Services\Settings\Seat;
use Seat\Slackbot\Events\SlackEventHandler;
use Seat\Slackbot\Exceptions\SlackSettingException;
use Seat\Slackbot\Helpers\SlackApi;

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
        
        // call rtm method in order to get a fresh new WSS uri
        $api = new SlackApi($token);
        $wss = $api->rtmStart();

        // use the Web Socket Service uri in order to connect to the Slack team
        \Ratchet\Client\connect($wss)->then(function($conn){
            $debug = false;
            $conn->on('message', function($msg) use ($conn, $debug){
                if ($debug == false) {
                    $dump = print_r($msg, true);
                    Log::debug($dump);
                    $debug = true;
                    echo "done";
                }
            });
/*
            $conn->on('team_join', function($msg) use ($conn){
                print_r('team_join');
            });

            $conn->on('group_joined', function($msg) use ($conn){
                print_r('group_joined');
            });

            $conn->on('channel_created', function($msg) use ($conn){
                print_r('channel_created');
            });

            $conn->on('channel_deleted', function($msg) use ($conn){
                print_r('channel_deleted');
            });

            $conn->on('group_archive', function($msg) use ($conn){
                Log::info($msg);
                Log::info('group archived');
            });

            $conn->on('group_unarchive', function($msg) use ($conn){
                print_r('group_unarchive');
            });
*/
        });

        /*
        $bot = new Bot();
        $bot->setToken($token);

        // catch all event and return them to SlackTeamJoin
        $bot->loadCatchAllCommand(new SlackEventHandler());
        $bot->run();
        */

        return;
    }
}
