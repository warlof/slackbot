<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 24/06/2016
 * Time: 16:28
 */

namespace Seat\Slackbot\Services {
    
    use PhpSlackBot\Bot;
    use Seat\Slackbot\Helpers\SlackApi;
    use Seat\Slackbot\Services\Commands\SlackTeamJoin;

    class SlackRtmDaemon
    {
        public function call()
        {
            $bot = new Bot();
            $bot->setToken(SlackApi::getSlackToken());
            
            // catch all event and return them to SlackTeamJoin
            $bot->loadCatchAllCommand(new SlackTeamJoin());
            $bot->run();

            return;
        }
    }
}