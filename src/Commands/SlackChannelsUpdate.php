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

class SlackChannelsUpdate extends Command
{
    protected $signature = 'slack:channels:update';

    protected $description = 'Discovering Slack channels (both public and private)';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $token = Seat::get('slack_token');

        if ($token == null) {
            throw new SlackSettingException("missing slack_token in settings");
        }

        $api = new SlackApi($token);

        $channels = array_merge($api->channels(false), $api->channels(true));

        foreach ($channels as $channel) {
            $slackChannel = SlackChannel::find($channel['id']);
            $isGroup = true;
            $isGeneral = false;

            // Determine if this is a group (private channel) or a channel
            if (substr($channel['id'], 0, 1) === 'C') {
                $isGroup = false;
            }

            if ($isGroup == false) {
                $isGeneral = (boolean) $channel['is_general'];
            }

            if ($slackChannel == null) {
                SlackChannel::create([
                    'id' => $channel['id'],
                    'name' => $channel['name'],
                    'is_group' => $isGroup,
                    'is_general' => $isGeneral
                ]);

                continue;
            }

            $slackChannel->update([
                'name' => $channel['name'],
                'is_general' => $isGeneral
            ]);
        }
    }
}
