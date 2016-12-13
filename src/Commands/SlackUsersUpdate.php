<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 18:51
 */

namespace Warlof\Seat\Slackbot\Commands;


use Illuminate\Console\Command;
use Seat\Web\Models\User;
use Warlof\Seat\Slackbot\Exceptions\SlackSettingException;
use Warlof\Seat\Slackbot\Helpers\SlackApi;
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
        $token = setting('slack_token', true);

        if ($token == null) {
            throw new SlackSettingException("missing slack_token in settings");
        }

        // get members list from slack team
        app()->singleton('warlof.slackbot.slack', function() use ($token){
            return new SlackApi($token);
        });

        $members = app('warlof.slackbot.slack')->members();

        // iterate over each member, check if the user mail match with a seat account and update the relation table
        foreach ($members as $m) {
            if ($m['id'] != 'USLACKBOT' && $m['deleted'] == false && $m['is_bot'] ==  false &&
                !key_exists('api_app_id', $m['profile'])) {
                $user = User::where('email', '=', $m['profile']['email'])->first();
                if ($user != null) {
                    $slackUser = SlackUser::find($user->id);
                    if ($slackUser == null) {
                        $slackUser = new SlackUser();
                        $slackUser->user_id = $user->id;
                        $slackUser->invited = true;
                    }

                    $slackUser->slack_id = $m['id'];
                    $slackUser->save();
                }
            }
        }
    }
}
