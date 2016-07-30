<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 18:51
 */

namespace Seat\Slackbot\Commands;


use Illuminate\Console\Command;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Factory;
use Seat\Services\Settings\Seat;
use Seat\Slackbot\Exceptions\SlackSettingException;
use Seat\Slackbot\Helpers\SlackApi;
use Seat\Slackbot\Models\SlackChannel;
use Seat\Slackbot\Models\SlackUser;

class SlackDaemon extends Command
{
    protected $signature = 'slack:daemon:run';

    protected $description = 'Slack service which handle Slack event. Mandatory in order to keep slack user and channels up to date.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $token = Seat::get('slack_token');

        if ($token == null)
            throw new SlackSettingException("missing slack_token in settings");

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
                        $slackUser = SlackUser::join('users', 'users.id', 'slack_users.user_id')
                            ->where('email', $slackMessage['user']['profile']['email'])
                            ->first();

                        if ($slackUser != null) {
                            $slackUser->update(['slack_id' => $slackMessage['user']['id']]);
                        }

                        break;

                    // if the event is of type "channel_created", then update our Slack channel table using new slack channel id
                    case 'group_joined':
                    case 'channel_created':
                        $channel = SlackChannel::find($slackMessage['channel']);

                        if ($channel->count() == 0) {
                            $channel = new SlackChannel();
                            $channel->id = $slackMessage['channel']['id'];
                            $channel->name = $slackMessage['channel']['name'];
                            // set private channel flag to true by default
                            $channel->is_group = true;

                            if ($slackMessage['type'] == 'channel_created') {
                                $channel->is_group = false;
                            }

                            $channel->save();
                        }

                        break;
                    // if the event is of type "channel_delete", then remove the record from our Slack channel table
                    case 'channel_deleted':
                    case 'group_archive':
                        $channel = SlackChannel::find($slackMessage['channel']);

                        if ($channel->count() != 0)
                            $channel->delete();

                        break;
                    case 'group_unarchive':
                        // load token and team uri from settings
                        $token = Seat::get('slack_token');

                        if ($token == null)
                            throw new SlackSettingException("missing slack_token in settings");

                        $slackApi = new SlackApi($token);
                        $apiGroup = $slackApi->info($slackMessage['channel'], true);

                        $group = new SlackChannel();
                        $group->id = $apiGroup['id'];
                        $group->name = $apiGroup['name'];
                        $group->is_group = true;
                        $group->save();

                        break;
                    case 'channel_rename':
                    case 'group_rename':
                        $channel = SlackChannel::find($slackMessage['channel']['id']);

                        if ($channel->count() != 0) {
                            $channel->update([
                                'name' => $slackMessage['channel']['name']
                            ]);
                        }

                        break;
                }
            });
        },
        function(\Exception $e) use ($loop) {
            echo $e->getMessage();
            $loop->stop();
        });

        $loop->run();

        return;
    }
}
