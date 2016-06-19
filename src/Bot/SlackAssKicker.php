<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 19:04
 */

namespace Seat\Slackbot\Bot;

use Seat\Eveapi\Models\Eve\ApiKey;
use Seat\Slackbot\Exceptions\SlackChannelException;
use Seat\Slackbot\Exceptions\SlackGroupException;
use Seat\Slackbot\Models\SlackUser;
use Seat\Web\Models\User;

class SlackAssKicker extends AbstractSlack
{
    function call()
    {
        // todo load team and token

        foreach (User::where('active', true)->get() as $user) {

            $keys = ApiKey::where('user_id', $user->id)->get();
            $slack_user = SlackUser::where('user_id', $user->id)->get();

            if ($this->isInvited($user)) {
                
                $channels = $this->memberOfChannels($slack_user);
                
                if (!$this->isEnabledKey($keys) || !$this->isActive($keys)) {
                    $this->processChannelsKick($slack_user, $channels);
                    $this->processGroupsKick($slack_user, $channels);
                } else {
                    $allowed_channels = $this->allowed_channels($slack_user);

                    // remove channels in which user is already in from all granted channels and invite him
                    $this->processChannelsKick($slack_user, array_diff($allowed_channels, $channels));
                    // remove granted channels from channels in which user is already in and kick him
                    $this->processGroupsKick($slack_user, array_diff($channels, $allowed_channels));
                }
            }
        }

        return;
    }

    /**
     * Determine in which channels an user is in
     *
     * @param SlackUser $slack_user
     * @throws SlackChannelException
     * @return array
     */
    function memberOfChannels(SlackUser $slack_user)
    {
        $inChannels = [];

        $params = [
            'token' => $this->slackTokenApi,
        ];

        // get all channels from the attached slack team
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, AbstractSlack::SLACK_URI_PATTERN . '/channels.list');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = json_decode(curl_exec($curl));

        if ($result == null || $result['ok'] == false) {
            throw new SlackChannelException("An error occurred while trying to kick the member.\r\n" .
                curl_error($curl));
        }

        // iterate over channels and check if the current slack user is part of channel
        foreach ($result['channels'] as $channel) {
            if (in_array($slack_user->slack_id, $channel['members']))
                $inChannels[] = $channel['id'];
        }

        return $inChannels;
    }

    /**
     * Kick an user from each channel
     *
     * @param SlackUser $slack_user
     * @param $channels
     * @throws SlackChannelException
     */
    function processChannelsKick(SlackUser $slack_user, $channels)
    {
        foreach ($channels as $channel) {
            $params = [
                'channel' => $channel,
                'token' => $this->slackTokenApi,
                'user' => $slack_user->slack_id
            ];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, AbstractSlack::SLACK_URI_PATTERN . '/channels.kick');
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $result = json_decode(curl_exec($curl));

            if ($result == null || $result['ok'] == false) {
                throw new SlackChannelException("An error occurred while trying to kick the member.\r\n" .
                    curl_error($curl));
            }
        }
    }

    /**
     * Kick an user from each group
     *
     * @param SlackUser $slack_user
     * @param $groups
     * @throws SlackGroupException
     */
    function processGroupsKick(SlackUser $slack_user, $groups)
    {
        foreach ($groups as $group) {
            $params = [
                'channel' => $group,
                'token' => $this->slackTokenApi,
                'user' => $slack_user->slack_id
            ];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, AbstractSlack::SLACK_URI_PATTERN . '/groups.kick');
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $result = json_decode(curl_exec($curl));

            if ($result == null || $result['ok'] == false) {
                throw new SlackGroupException("An error occurred while trying to kick the member.\r\n" .
                    curl_error($curl));
            }
        }
    }

}