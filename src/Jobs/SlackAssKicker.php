<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 19:04
 */

namespace Seat\Slackbot\Jobs;

use Seat\Eveapi\Models\Eve\ApiKey;
use Seat\Slackbot\Exceptions\SlackChannelException;
use Seat\Slackbot\Exceptions\SlackGroupException;
use Seat\Slackbot\Helpers\SlackApi;
use Seat\Slackbot\Models\SlackUser;

class SlackAssKicker extends AbstractSlack
{
    function call()
    {
        // call the parent call method in order to load the Slack Api Token
        parent::call();

        // get all Api Key owned by the user
        $keys = ApiKey::where('user_id', $this->user->id)->get();
        // get the Slack Api User
        $slackUser = SlackUser::where('user_id', $this->user->id)
            ->where('invited', true)
            ->whereNotNull('slack_id')
            ->first();

        if ($slackUser != null) {

            // get channels into which current user is already member
            $channels = $this->memberOfChannels($slackUser);

            // if key are not valid OR account no longer paid
            // kick the user from all channels to which he's member
            if ($this->isEnabledKey($keys) == false || $this->isActive($keys) == false) {

                $this->processChannelsKick($slackUser, $channels);
                //$this->processGroupsKick($slackUser, $channels);

            // in other way, compute the gap and kick only the user
            // to channel from which he's no longer granted to be in
            } else {
                $allowedChannels = $this->allowedChannels($slackUser);

                // remove channels in which user is already in from all granted channels and invite him
                $this->processChannelsKick($slackUser, array_diff($channels, $allowedChannels));
                // remove granted channels from channels in which user is already in and kick him
                //$this->processGroupsKick($slackUser, array_diff($channels, $allowedChannels));
            }
        }

        return;
    }

    /**
     * Kick an user from each channel
     *
     * @param SlackUser $slackUser
     * @param $channels
     * @throws SlackChannelException
     */
    function processChannelsKick(SlackUser $slackUser, $channels)
    {
        $params = [
            'channel' => '',
            'user' => $slackUser->slack_id
        ];

        // iterate channel ID and call kick method from Slack Api
        foreach ($channels as $channel) {
            $params['channel'] = $channel;

            $result = SlackApi::post('/channels.kick', $params);

            if ($result['ok'] == false) {
                throw new SlackChannelException($result['error']);
            }
        }
    }

    /**
     * Kick an user from each group
     *
     * @param SlackUser $slackUser
     * @param $groups
     * @throws SlackGroupException
     */
    function processGroupsKick(SlackUser $slackUser, $groups)
    {
        $params = [
            'channel' => '',
            'user' => $slackUser->slack_id
        ];

        // iterate group ID and call kick method from Slack Api
        foreach ($groups as $group) {
            $params['channel'] = $group;

            $result = SlackApi::post('/groups.kick', $params);

            if ($result['ok'] == false) {
                throw new SlackGroupException($result['error']);
            }
        }
    }

}
