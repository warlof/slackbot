<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 24/06/2016
 * Time: 16:28
 */

namespace Seat\Slackbot\Services {
    
    use PhpSlackBot\Bot;
    use Seat\Services\Settings\Seat;
    use Seat\Slackbot\Exceptions\SlackSettingException;
    use Seat\Slackbot\Services\Commands\SlackEventHandler;

    class SlackRtmDaemon
    {
        public function call()
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
}