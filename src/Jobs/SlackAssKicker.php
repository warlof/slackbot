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
use Seat\Slackbot\Models\SlackUser;

class SlackAssKicker extends AbstractSlack
{
    function call()
    {
        $this->load();

        $keys = ApiKey::where('user_id', $this->user->id)->get();
        $slackUser = SlackUser::where('user_id', $this->user->id)->first();

        if ($slackUser != null) {
            if ($this->isInvited($this->user)) {
                
                $channels = $this->memberOfChannels($slackUser);

                if (!$this->isEnabledKey($keys) || !$this->isActive($keys)) {
                    $this->processChannelsKick($slackUser, $channels);
                    $this->processGroupsKick($slackUser, $channels);
                } else {
                    $allowedChannels = $this->allowedChannels($slackUser);

                    // remove channels in which user is already in from all granted channels and invite him
                    $this->processChannelsKick($slackUser, array_diff($channels, $allowedChannels));
                    // remove granted channels from channels in which user is already in and kick him
                    $this->processGroupsKick($slackUser, array_diff($channels, $allowedChannels));
                }
            }
        }

        return;
    }

    /**
     * Determine in which channels an user is in
     *
     * @param SlackUser $slackUser
     * @throws SlackChannelException
     * @return array
     */
    function memberOfChannels(SlackUser $slackUser)
    {
        $inChannels = [];
        
        // get all channels from the attached slack team
        $result = $this->processSlackApiPost('/channels.list');

        if ($result['ok'] == false) {
            throw new SlackChannelException($result['error']);
        }
        
        // iterate over channels and check if the current slack user is part of channel
        foreach ($result['channels'] as $channel) {
            if (in_array($slackUser->slack_id, $channel['members']))
                $inChannels[] = $channel['id'];
        }

        return $inChannels;
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

        foreach ($channels as $channel) {
            $params['channel'] = $channel;

            $result = $this->processSlackApiPost('/channels.kick', $params);

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

        foreach ($groups as $group) {
            $params['channel'] = $group;

            $result = $this->processSlackApiPost('/groups.kick', $params);

            if ($result['ok'] == false) {
                throw new SlackGroupException($result['error']);
            }
        }
    }

}
