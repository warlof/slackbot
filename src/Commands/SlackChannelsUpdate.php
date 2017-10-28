<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 18:51
 */

namespace Warlof\Seat\Slackbot\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Warlof\Seat\Slackbot\Exceptions\SlackSettingException;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Repositories\SlackApi;

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
        if (setting('warlof.slackbot.credentials.access_token', true) == null) {
            throw new SlackSettingException("missing warlof.slackbot.credentials.access_token in settings");
        }

        $channelsAndGroups = Redis::keys('seat:warlof:slackbot:conversations*');
        foreach ($channelsAndGroups as $channelOrGroup) {
            Redis::del($channelOrGroup);
        }

        // make a call in order to fetch both public and private channels
        $channels = app(SlackApi::class)->channels();

        $slackChannelIds = [];

        // iterate over each slack channel and create or update information from SeAT
        foreach ($channels as $channel) {
            // init channels ids array which will be used later in order to remove outdate channels
            $slackChannelIds[] = $channel['id'];

            // try to get channel object from SeAT
            $slackChannel = SlackChannel::find($channel['id']);

            // create the channel if it doesn't exist
            if ($slackChannel == null) {
                SlackChannel::create([
                    'id' => $channel['id'],
                    'name' => $channel['name'],
                    'is_group' => (strpos($channel['id'], 'C') === 0) ? false : true,
                    'is_general' => (strpos($channel['id'], 'C') === 0) ? $channel['is_general'] : false
                ]);

                continue;
            }

            // update the channel if it is already known by SeAT
            $slackChannel->update([
                'name' => $channel['name'],
                'is_general' => (strpos($channel['id'], 'C') === 0) ? $channel['is_general'] : false
            ]);

            $redisKey = 'seat:warlof:slackbot:conversations.' . $channel['id'];

            Redis::set($redisKey, json_encode($channel));
        }

        // get all known channels from SeAT and remove them if they are no longer existing
        SlackChannel::whereNotIn('id', $slackChannelIds)->delete();
    }
}
