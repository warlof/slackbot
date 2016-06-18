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
                
                $channels = $this->member_of_channels($slack_user);
                
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
    function member_of_channels(SlackUser $slack_user)
    {
        $in_channels = [];

        $params = [
            'token' => $this->slack_token_api,
        ];

        // get all channels from the attached slack team
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, AbstractSlack::SLACK_URI_PATTERN . '/channels.list');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if (!$result = curl_exec($curl)) {
            throw new SlackChannelException("An error occurred while trying to kick the member.\r\n" .
                curl_error($curl));
        } else {
            $json = json_decode($result);
            if ($json['ok'] != true) {
                throw new SlackChannelException("An error occurred while trying to kick the member.\r\n" .
                    $json['error']);
            }

            // iterate over channels and check if the current slack user is part of channel
            foreach ($json['channels'] as $channel) {
                if (in_array($slack_user->slack_id, $channel['members']))
                    $in_channels[] = $channel['id'];
            }
        }

        return $in_channels;
    }

    /**
     * Kick an user from each channel
     *
     * @param SlackUser $slack_user
     * @param $channel_ids
     * @throws SlackChannelException
     */
    function processChannelsKick(SlackUser $slack_user, $channel_ids)
    {
        foreach ($channel_ids as $channel_id) {
            $params = [
                'channel' => $channel_id,
                'token' => $this->slack_token_api,
                'user' => $slack_user->slack_id
            ];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, AbstractSlack::SLACK_URI_PATTERN . '/channels.kick');
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            if (!$result = curl_exec($curl)) {
                throw new SlackChannelException("An error occurred while trying to kick the member.\r\n" .
                    curl_error($curl));
            } else {
                $json = json_decode($result);
                if ($json['ok'] != true) {
                    throw new SlackChannelException("An error occurred while trying to kick the member.\r\n" .
                        $json['error']);
                }
            }
        }
    }

    /**
     * Kick an user from each group
     *
     * @param SlackUser $slack_user
     * @param $group_ids
     * @throws SlackGroupException
     */
    function processGroupsKick(SlackUser $slack_user, $group_ids)
    {
        foreach ($group_ids as $group_id) {
            $params = [
                'channel' => $group_id,
                'token' => $this->slack_token_api,
                'user' => $slack_user->slack_id
            ];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, AbstractSlack::SLACK_URI_PATTERN . '/groups.kick');
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            if (!$result = curl_exec($curl)) {
                throw new SlackGroupException("An error occurred while trying to kick the member.\r\n" .
                    curl_error($curl));
            } else {
                $json = json_decode($result);
                if ($json['ok'] != true) {
                    throw new SlackGroupException("An error occurred while trying to kick the member.\r\n" .
                        $json['error']);
                }
            }
        }
    }

}