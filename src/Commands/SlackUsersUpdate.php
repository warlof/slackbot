<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 18:51
 */

namespace Warlof\Seat\Slackbot\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Seat\Web\Models\User;
use Warlof\Seat\Slackbot\Exceptions\SlackSettingException;
use Warlof\Seat\Slackbot\Models\SlackUser;

class SlackUsersUpdate extends Command
{
    protected $signature = 'slack:users:update';

    protected $description = 'Discovering Slack users';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if (setting('warlof.slackbot.credentials.access_token', true) == null) {
            throw new SlackSettingException("missing warlof.slackbot.credentials.access_token in settings");
        }

        // get members list from slack team
        $members = app('warlof.slackbot.slack')->members();

        // iterate over each member and try to make aggregation
        foreach ($members as $member) {
            // exclude SLACK BOT user from user list
            // exclude bot from user list
            // exclude token owner from list
            if ($member['id'] != 'USLACKBOT' && $member['deleted'] == false && $member['is_bot'] ==  false &&
                !key_exists('api_app_id', $member['profile'])) {

                // if it appears to be a new user (at least, unknown from SeAT
                if (($slackUser = SlackUser::where('slack_id', $member['id'])->first()) == null) {

                    // and we're able to match him using email address
                    if (($seatUser = User::where('email', $member['profile']['email'])->first()) != null) {

                        // so, we create the association
                        SlackUser::create([
                            'user_id' => $seatUser->id,
                            'slack_id' => $member['id']
                        ]);

                        Redis::set('seat:warlof:slackbot:users.' . $member['id'], json_encode($member));
                    }
                }
            }
        }
    }
}
