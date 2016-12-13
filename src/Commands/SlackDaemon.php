<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 18:51
 */

namespace Warlof\Seat\Slackbot\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Factory;
use Warlof\Seat\Slackbot\Exceptions\SlackSettingException;
use Warlof\Seat\Slackbot\Helpers\SlackApi;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Models\SlackUser;

class SlackDaemon extends Command
{
    protected $signature = 'slack:daemon:run';

    protected $description = 'Slack service which handle Slack event.' .
        ' Mandatory in order to keep slack user and channels up to date.';

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

        // call rtm method in order to get a fresh new WSS uri
        $api = new SlackApi($token);
        $wss = $api->rtmStart();

        // start a loop event which will handle RTM daemon
        $loop = Factory::create();
        $connector = new Connector($loop);

        // prepare the event catcher (we only care about members join and channels)
        $connector($wss)->then(function(WebSocket $conn) {
            // trigger on RTM message event
            $conn->on('message', function(MessageInterface $msg) use ($conn){
                // since Slack RTM return json message, decode it first
                $slackMessage = json_decode($msg, true);

                // then, process to channel, groups and member case
                switch ($slackMessage['type']) {
                    // if the event is of type "team_join", then update our Slack user table using the new slack user id
                    // common element between SeAT and Slack is mail address
                    case 'team_join':
                        $this->newMember($slackMessage['user']);
                        break;
                    // if the event is of type "channel_created"
                    // then update our Slack channel table using new slack channel id
                    case 'group_joined':
                    case 'channel_created':
                        $this->createChannel($slackMessage['channel']);
                        break;
                    // if the event is of type "channel_delete", then remove the record from our Slack channel table
                    case 'channel_deleted':
                    case 'group_archive':
                        SlackChannel::destroy($slackMessage['channel']);
                        break;
                    case 'group_unarchive':
                        logger()->debug('[Slackbot][Daemon][group_unarchive] ' . print_r($slackMessage, true));

                        $this->restoreGroup($slackMessage['channel']);
                        break;
                    case 'channel_rename':
                    case 'group_rename':
                        $this->renameChannel($slackMessage['channel']);
                        break;
                }
            });
        },
        function(\Exception $e) use ($loop) {
            logger()->error($e->getMessage());
            $loop->stop();
        });

        $loop->run();

        return;
    }

    private function newMember($userInformation)
    {
        $slackUser = SlackUser::join('users', 'users.id', '=', 'slack_users.user_id')
            ->where('email', $userInformation['profile']['email'])
            ->first();

        if ($slackUser != null) {
            $slackUser->update(['slack_id' => $userInformation['id']]);
        }
    }

    private function createChannel($channelInformation)
    {
        $channel = SlackChannel::find($channelInformation['id']);

        $group = true;

        // Determine if this is a group (private channel) or a channel
        if (substr($channelInformation['id'], 0, 1) === 'C') {
            $group = false;
        }

        if ($channel == null) {
            SlackChannel::create([
                'id' => $channelInformation['id'],
                'name' => $channelInformation['name'],
                'is_group' => $group
            ]);
        }
    }

    private function renameChannel($channelInformation)
    {
        $channel = SlackChannel::find($channelInformation['id']);

        if ($channel != null) {
            $channel->update([
                'name' => $channelInformation['name']
            ]);

            return;
        }

        $this->createChannel($channelInformation);
    }

    private function restoreGroup($groupId)
    {
        // load token and team uri from settings
        $token = setting('slack_token', true);

        if ($token == null) {
            throw new SlackSettingException("missing slack_token in settings");
        }

        $slackApi = new SlackApi($token);
        $apiGroup = $slackApi->info($groupId, true);

        SlackChannel::create([
            'id' => $apiGroup['id'],
            'name' => $apiGroup['name'],
            'is_group' => true
        ]);
    }
}
