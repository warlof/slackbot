<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 26/06/2016
 * Time: 10:46
 */

namespace Seat\Slackbot\Services\Commands;


use PhpSlackBot\Command\BaseCommand;
use Seat\Slackbot\Models\SlackUser;

class SlackTeamJoin extends BaseCommand
{
    protected function configure()
    {
        // We don't have to configure a command name in this case
    }

    protected function execute($data, $context)
    {
        // if the event is of type "team_join", then update our Slack user table using the new slack user id
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