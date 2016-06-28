<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 26/06/2016
 * Time: 10:58
 */

namespace Seat\Slackbot\Helpers;

use Seat\Slackbot\Exceptions\SlackApiException;
use Seat\Slackbot\Exceptions\SlackChannelException;
use Seat\Slackbot\Exceptions\SlackGroupException;
use Seat\Slackbot\Exceptions\SlackMailException;
use Seat\Slackbot\Exceptions\SlackSettingException;
use Seat\Slackbot\Exceptions\SlackTeamInvitationException;

class SlackApi
{
    /**
     * Determine the base slack api uri
     */
    const SLACK_URI_PATTERN = "https://slack.com/api";

    private $token;

    public function __construct($token)
    {
        $this->token = $token;
    }
    
    /**
     * Make a post action to the Slack API
     * 
     * @param string $endpoint Slack API method
     * @param array $parameters Slack API parameters (except token)
     * @return array An array from the Slack API response (json parsed)
     * @throws SlackApiException
     * @throws SlackSettingException
     */
    private function post($endpoint, $parameters = [])
    {
        // add slack token to the post parameters
        $parameters['token'] = $this->token;

        // prepare curl request using passed parameters and endpoint
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, self::SLACK_URI_PATTERN . $endpoint);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($parameters));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = json_decode(curl_exec($curl), true);

        if ($result == null) {
            throw new SlackApiException("An error occurred while calling the Slack API\r\n" . curl_error($curl));
        }

        return $result;
    }

    /**
     * Invite an user to the Slack team using a specific mail address
     * 
     * @param string $mail The new user mail
     * @throws SlackApiException
     * @throws SlackMailException
     * @throws SlackTeamInvitationException
     */
    public function inviteToTeam($mail)
    {
        $params = [
            'email' => $mail,
            'set_active' => true
        ];

        // check that the user mail is not a "random" mail
        if (preg_match('/.local/i', $mail) === 1) {
            throw new SlackMailException();
        }

        // call invite endpoint from Slack Api in order to invite the user to the team
        $result = $this->post('/users.admin.invite', $params);

        if ($result['ok'] == false) {
            throw new SlackTeamInvitationException($result['error']);
        }
    }

    /**
     * Determine in which channels an user is currently in
     *
     * @param string $slackId Slack user id (ie: U3216587)
     * @param string $type channel or group
     * @throws SlackApiException
     * @throws SlackChannelException
     * @return array
     */
    public function memberOf($slackId, $type)
    {
        $channels = [];
        
        switch ($type) {
            case 'channels':
                // get all channels from the attached slack team
                $result = $this->post('/channels.list');
                break;
            case 'groups':
                break;
            default:
                throw new SlackApiException('Unsupported call type for memberOf method');
        }

        if ($result['ok'] == false) {
            throw new SlackChannelException($result['error']);
        }

        // iterate over channels and check if the current slack user is part of channel
        foreach ($result[$type] as $channel) {
            if (in_array($slackId, $channel['members']))
                $channels[] = $channel['id'];
        }

        return $channels;
    }

    /**
     * Get information from a specific channel
     *
     * @param string $channelId Slack channel id (ie: C465478)
     * @return array
     * @throws SlackApiException
     * @throws SlackChannelException
     */
    public function channelInfo($channelId)
    {
        $params = [
            'channel' => $channelId
        ];

        $result = $this->post('/channels.info', $params);

        if ($result['ok'] == false) {
            throw new SlackChannelException($result['error']);
        }

        return $result['channel'];
    }

    /**
     * Invite an user into a specific channel
     *
     * @param string $userId Slack user id (ie: U3216587)
     * @param string $channelId Slack channel id (ie: C6547987)
     * @throws SlackApiException
     * @throws SlackChannelException
     */
    public function inviteToChannel($userId, $channelId)
    {
        $params = [
            'channel' => $channelId,
            'user' => $userId
        ];

        $channel = $this->channelInfo($channelId);
        
        if (in_array($userId, $channel['members']) == false) {
            $result = $this->post('/channels.invite', $params);

            if ($result['ok'] == false) {
                throw new SlackChannelException($result['error']);
            }
        }
    }

    /**
     * Kick an user from a specific channel
     * 
     * @param string $userId Slack user id (ie: U3216587)
     * @param string $channelId Slack channel id (ie: C6547987)
     * @throws SlackApiException
     * @throws SlackChannelException
     */
    public function kickFromChannel($userId, $channelId)
    {
        $params = [
            'channel' => $channelId,
            'user' => $userId
        ];

        $channel = $this->channelInfo($channelId);

        // user can only be kicked from non general channel and if it is already member of it (legit)
        if ($channel['is_general'] == false && in_array($userId, $channel['members']) == true) {
            $result = $this->post('/channels.kick', $params);

            if ($result['ok'] == false) {
                throw new SlackChannelException($result['error']);
            }
        }
    }
    
    /**
     * Get information from a specific group
     *
     * @param string $groupId Slack group id (ie: G979754)
     * @return array
     * @throws SlackApiException
     * @throws SlackGroupException
     */
    public function groupInfo($groupId)
    {
        $params = [
            'channel' => $groupId
        ];

        $result = $this->post('/groups.info', $params);

        if ($result['ok'] == false) {
            throw new SlackGroupException($result['error']);
        }

        return $result['group'];
    }

    /**
     * Invite an user into a specific group
     *
     * @param string $userId Slack user id (ie: U3216587)
     * @param string $groupId Slack group id (ie: G7975464)
     * @throws SlackApiException
     * @throws SlackGroupException
     */
    public function inviteToGroup($userId, $groupId)
    {
        $params = [
            'channel' => $groupId,
            'user' => $userId
        ];

        $group = $this->groupInfo($groupId);
        
        if (in_array($userId, $group['members']) == false) {

            $result = $this->post('/groups.invite', $params);

            if ($result['ok'] == false) {
                throw new SlackGroupException($result['error']);
            }
        }
    }

    /**
     * Kick an user from a specific group
     *
     * @param string $userId Slack user id (ie: U3216587)
     * @param string $groupId Slack group id (ie: G7975464)
     * @throws SlackApiException
     * @throws SlackGroupException
     */
    public function kickFromGroup($userId, $groupId)
    {
        $params = [
            'channel' => $groupId,
            'user' => $userId
        ];

        $group = $this->groupInfo($groupId);

        if (in_array($userId, $group['members']) == true) {

            $result = $this->post('/groups.kick', $params);

            if ($result['ok'] == false) {
                throw new SlackGroupException($result['error']);
            }
        }
    }
}