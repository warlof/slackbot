<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 26/06/2016
 * Time: 10:58
 */

namespace Warlof\Seat\Slackbot\Repositories;

use Warlof\Seat\Slackbot\Exceptions\SlackApiException;
use Warlof\Seat\Slackbot\Exceptions\SlackChannelException;
use Warlof\Seat\Slackbot\Exceptions\SlackGroupException;
use Warlof\Seat\Slackbot\Exceptions\SlackMailException;
use Warlof\Seat\Slackbot\Exceptions\SlackTeamInvitationException;
use Warlof\Seat\Slackbot\Exceptions\SlackUserException;

class SlackApi
{
    /**
     * Determine the base slack api uri
     */
    const SLACK_URI_PATTERN = "https://slack.com/api";

    /**
     * @var string The Slack token
     */
    private $token;

    /**
     * @var string The Slack token Owner User ID
     */
    private $tokenOwnerId;

    /**
     * SlackApi constructor.
     * @param $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;

        $tokenInfo = $this->tokenInformation();
        $this->tokenOwnerId = $tokenInfo['user_id'];
    }

    /**
     * Return information about current token
     * ['ok' => true,
     * 'url' => 'slack team uri',
     * 'team' => 'slack team name',
     * 'user' => 'token owner name',
     * 'team_id' => 'slack team id',
     * 'user_id' => 'slack team id']
     *
     * @throws SlackApiException
     * @return array
     */
    public function tokenInformation() : array
    {
        $result = $this->post('/auth.test');

        if ($result['ok'] == false) {
            throw new SlackApiException($result['error']);
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
     * @deprecated Since not an official endpoint and live OAuth tokens don't grant access to it
     */
    public function inviteToTeam(string $mail)
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

        // check that the request has been handled successfully. If not, fire an exception
        if ($result['ok'] == false) {
            throw new SlackTeamInvitationException($result['error']);
        }
    }

    /**
     * Determine in which channels an user is currently in
     *
     * @param string $slackId Slack user id (ie: U3216587)
     * @param boolean $private Determine if channels should be private (group) or public (channel)
     * @throws SlackApiException
     * @throws SlackChannelException
     * @throws SlackGroupException
     * @return array
     */
    public function memberOf(string $slackId, bool $private) : array
    {
        $channels = [];
        $type = 'channels';

        if ($private) {
            $type = 'groups';
        }

        // we will use /channels.list endpoint if $private is false
        // in other case, it will be /groups.list
        $endpoint = '/' . $type . '.list';

        // send the query to SlackAPI and fetch result
        $result = $this->post($endpoint);

        // ensure that the request has been handle successfully
        // if not, throw an exception according to public or private case
        if ($result['ok'] == false) {
            if ($private) {
                throw new SlackGroupException($result['error']);
            }

            throw new SlackChannelException($result['error']);
        }

        // iterate over channels and check if the current slack user is part of channel
        foreach ($result[$type] as $channel) {
            // exclude private chat (is_mpim) from result if $private is true
            if (($type == 'groups' && $channel['is_mpim'] == false) || $type == 'channels' &&
                ($type == 'channels' && $channel['is_general'] == false)) {
                // search for Slack User ID into every channel members list
                // if we find it, append the channel id to the result
                if (in_array($slackId, $channel['members'])) {
                    $channels[] = $channel['id'];
                }
            }
        }

        return $channels;
    }

    /**
     * Get information from a specific channel
     *
     * @param string $channelId Slack channel id (ie: C465478)
     * @param boolean $private Determine if channels should be private (group) or public (channel)
     * @return array
     * @throws SlackApiException
     * @throws SlackGroupException
     * @throws SlackChannelException
     */
    public function info(string $channelId, bool $private) : array
    {
        $params = [
            'channel' => $channelId
        ];

        // The request is for a private channel, call groups endpoint
        if ($private) {
            $result = $this->post('/groups.info', $params);

            // check that the request has been handled successfully. If not, fire an exception
            if ($result['ok'] == false) {
                throw new SlackGroupException($result['error']);
            }
            
            return $result['group'];
        }

        // The request is for a public channel, call channels endpoint
        $result = $this->post('/channels.info', $params);

        // check that the request has been handled successfully. If not, fire an exception
        if ($result['ok'] == false) {
            throw new SlackChannelException($result['error']);
        }

        // return only channel array which contains information
        return $result['channel'];
    }

    /**
     * Invite an user into a specific channel
     *
     * @param string $userId Slack user id (ie: U3216587)
     * @param string $channelId Slack channel id (ie: C6547987)
     * @param boolean $private Determine if channels should be private (group) or public (channel)
     * @throws SlackApiException
     * @return bool
     */
    public function invite(string $userId, string $channelId, bool $private) : bool
    {
        // set parameters for Slack request, channel id and user id
        $params = [
            'channel' => $channelId,
            'user' => $userId
        ];

        // The request is for a private channel, call groups endpoint
        if ($private) {

            $result = $this->post('/groups.invite', $params);

            return $result['ok'];
        }

        // The request is for a public channel, call channels endpoint
        $result = $this->post('/channels.invite', $params);

        return $result['ok'];
    }

    /**
     * Kick an user from a specific channel
     * 
     * @param string $userId Slack user id (ie: U3216587)
     * @param string $channelId Slack channel id (ie: C6547987)
     * @param boolean $private Determine if channels should be private (group) or public (channel)
     * @throws SlackApiException
     * @return bool
     */
    public function kick(string $userId, string $channelId, bool $private) : bool
    {
        // set parameters for Slack request, channel id and user id
        $params = [
            'channel' => $channelId,
            'user' => $userId
        ];

        // We can't kick token owner himself
        if ($userId == $this->tokenOwnerId) {
            return false;
        }

        // The request is for a private channel, call groups endpoint
        if ($private) {
            // send a request to Slack in order to kick the user from the channel
            $result = $this->post('/groups.kick', $params);

            return $result['ok'];
        }

        // The request is for a public channel, call channels endpoint
        $channel = $this->info($channelId, $private);

        // user can only be kicked from non general channel and if it is already member of it (legit)
        if ($channel['is_general'] == false && in_array($userId, $channel['members']) == true) {
            // send a request to Slack in order to kick the user from the channel
            $result = $this->post('/channels.kick', $params);

            return $result['ok'];
        }

        return false;
    }

    /**
     * Return channels or groups list
     * 
     * @param boolean $private Determine if channels should be private (group) or public (channel)
     * @return array
     * @throws SlackApiException
     * @throws SlackChannelException
     * @throws SlackGroupException
     */
    public function channels(bool $private) : array
    {
        // we don't care from archived channels either they are public or private
        $params = [
            'exclude_archived' => 1
        ];

        // check $private value in order to determine which endpoint will be used
        if ($private) {
            // send request to Slack API and fetch result
            $result = $this->post('/groups.list', $params);

            // check that the request has been handled successfully. If not, fire an exception
            if ($result['ok'] == false) {
                throw new SlackGroupException($result['error']);
            }

            // we only care about private channel, iterate over the result and exclude the MPIM (private message)
            $data = [];
            foreach ($result['groups'] as $g) {
                if ($g['is_mpim'] == false) {
                    $data[] = $g;
                }
            }

            // return only channels array which handle channels information like id or name
            return $data;
        }

        // send request to Slack API and fetch result
        $result = $this->post('/channels.list', $params);

        // check that the request has been handled successfully. If not, fire an exception
        if ($result['ok'] == false) {
            throw new SlackChannelException($result['error']);
        }

        // return only channels array which handle channels information like id or name
        return $result['channels'];
    }

    /**
     * Return a list of team members
     *
     * @return array
     * @throws SlackApiException
     * @throws SlackUserException
     */
    public function members() : array
    {
        // send request to Slack API and fetch result
        $result = $this->post('/users.list');

        // check that the request has been handled successfully. If not, fire an exception
        if ($result['ok'] == false) {
            throw new SlackUserException($result['error']);
        }

        // return the slack team members list
        return $result['members'];
    }

    /**
     * Call rtm.start endpoint for RTM Api usage
     * It will return a short life token which should be used in order to connect to Slack Team using RTM
     *
     * @return string
     * @throws SlackApiException
     */
    public function rtmStart() : string
    {
        // send request to Slack API and fetch result
        $result = $this->post('/rtm.start');

        // check that the request has been handled successfully. If not, fire an exception
        if ($result['ok'] == false) {
            throw new SlackApiException($result['error']);
        }

        // return the short life token which should be used with RTM Api
        return $result['url'];
    }

    public function userInfo($slackId)
    {
        $result = $this->post('/users.info', ['user' => $slackId]);

        if ($result['ok'] == false) {
            throw new SlackApiException($result['error']);
        }

        return $result['user'];
    }

    /**
     * Make a post action to the Slack API
     *
     * @param string $endpoint Slack API method
     * @param array $parameters Slack API parameters (except token)
     * @return array An array from the Slack API response (json parsed)
     * @throws SlackApiException
     */
    private function post(string $endpoint, array $parameters = []) : array
    {
        // Process cool down according to Slack restriction
        // https://api.slack.com/docs/rate-limits
        sleep(1);

        // add slack token to the post parameters
        $parameters['token'] = $this->token;

        // prepare curl request using passed parameters and endpoint
        $curl = curl_init();
        // concatenate API endpoint with constant api URI
        curl_setopt($curl, CURLOPT_URL, self::SLACK_URI_PATTERN . $endpoint);
        // send all request using HTTP/POST
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($parameters));
        // ask curl to wait until server answer
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // inform Slack about who's sending the query
        curl_setopt($curl, CURLOPT_USERAGENT, 'Seat-Slackbot/0.x mail=loic.leuilliot@gmail.com');

        // Slack is talking with us using JSON, fetch the result and convert into array
        $result = json_decode(curl_exec($curl), true);

        // check that the request has been received successfully. If not, fire an exception
        if ($result == null) {
            throw new SlackApiException("An error occurred while calling the Slack API\r\n" . curl_error($curl));
        }

        return $result;
    }
}