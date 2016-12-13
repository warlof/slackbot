<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 18:51
 */

namespace Warlof\Seat\Slackbot\Commands;


use Illuminate\Console\Command;
use Seat\Services\Settings\Seat;
use Warlof\Seat\Slackbot\Exceptions\SlackSettingException;
use Warlof\Seat\Slackbot\Helpers\SlackApi;
use Warlof\Seat\Slackbot\Models\SlackChannel;

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

        // init Slack Api using token
        $api = new SlackApi($token);

        // make a call in order to fetch both public and private channels
        $channels = array_merge($api->channels(false), $api->channels(true));

        $slackChannelIds = [];

        // iterate over each slack channel and create or update information from SeAT
        foreach ($channels as $channel) {
            // init channels ids array which will be used later in order to remove outdate channels
            $slackChannelIds[] = $channel['id'];

            // init flags to default value
            $isGroup = true;
            $isGeneral = false;

            // try to get channel object from SeAT
            $slackChannel = SlackChannel::find($channel['id']);

            // Determine if this is a group (private channel) or a channel
            if (substr($channel['id'], 0, 1) === 'C') {
                $isGroup = false;
            }

            if ($isGroup == false) {
                $isGeneral = (boolean) $channel['is_general'];
            }

            // create the channel if it doesn't exist
            if ($slackChannel == null) {
                SlackChannel::create([
                    'id' => $channel['id'],
                    'name' => $channel['name'],
                    'is_group' => $isGroup,
                    'is_general' => $isGeneral
                ]);

                continue;
            }

            // update the channel if it is already known by SeAT
            $slackChannel->update([
                'name' => $channel['name'],
                'is_general' => $isGeneral
            ]);
        }

        // get all known channels from SeAT and remove them if they are no longer existing
        SlackChannel::whereNotIn('id', $slackChannelIds)->delete();
    }
}
