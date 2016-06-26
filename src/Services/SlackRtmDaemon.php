<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 24/06/2016
 * Time: 16:28
 */

namespace Seat\Slackbot\Services {
    
    use PhpSlackBot\Bot;
    use PhpSlackBot\Command\BaseCommand;
    use Seat\Slackbot\Jobs\AbstractSlack;
    use Seat\Slackbot\Models\SlackUser;

    class SlackRtmDaemon extends AbstractSlack
    {
        public function call()
        {
            $this->load();
            $bot = new Bot();
            $bot->setToken($this->slackTokenApi);
            $bot->loadCatchAllCommand(new SlackUserRegistration());
            $bot->run();

            return;
        }
    }

    class SlackUserRegistration extends BaseCommand
    {

        protected function configure()
        {
            // We don't have to configure a command name in this case
        }

        protected function execute($data, $context)
        {
            if ($data['type'] == 'team_join') {
                $slackUser = SlackUser::join('users', 'users.id', 'slack_users.user_id')
                    ->where('email', $data['user']['profile']['email'])
                    ->first();

                if ($slackUser != null) {
                    $slackUser->update(['slack_id' => $data['user']['id']]);
                }
            }
        }

    }
}