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

class SlackUpdateChannels extends Command
{
    protected $signature = 'slack:update:channels';

    protected $description = 'Discovering Slack channels (both public and private)';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $token = Seat::get('slack_token');

        if ($token == null)
            throw new SlackSettingException("missing slack_token in settings");
        
        $api = new SlackApi($token);

        $channels = $api->channels(false);
        $groups = $api->channels(true);

        foreach ($channels as $c) {
            $channel = SlackChannel::find($c['id']);

            if ($channel == null) {
                $channel = new SlackChannel();
                $channel->id = $c['id'];
                $channel->name = $c['name'];
                $channel->is_group = false;
                $channel->save();
            } else {
                $channel->update([
                    'name' => $c['name']
                ]);
            }
        }

        foreach ($groups as $g) {
            if ($g['is_mpim'] == false) {
                $group = SlackChannel::find($g['id']);

                if ($group == null) {
                    $group = new SlackChannel();
                    $group->id = $g['id'];
                    $group->name = $g['name'];
                    $group->is_group = true;
                    $group->save();
                } else {
                    $group->update([
                        'name' => $g['name']
                    ]);
                }
            }
        }
    }
}
