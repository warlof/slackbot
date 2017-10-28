<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 26/06/2016
 * Time: 10:58
 */

namespace Warlof\Seat\Slackbot\Repositories;

use Warlof\Seat\Slackbot\Exceptions\SlackApiException;
use Warlof\Seat\Slackbot\Exceptions\SlackConversationException;
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

        $tokenInfo = $this->getTokenInformation();
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
    public function getTokenInformation() : array
    {
        $result = $this->post('/auth.test');

        if ($result['ok'] == false) {
            throw new SlackApiException($result['error']);
        }

        return $result;
    }

    /**
     * Determine if an user is member of the specified channel
     *
     * @param string $slackId
     * @param string $channelId
     * @return bool
     */
    public function isMemberOf(string $slackId, string $channelId) : bool
    {
        $channels = $this->getUserConversations($slackId);

        return in_array($channelId, $channels);
    }

    /**
     * Determine in which channels an user is currently in
     *
     * @param string $slackId Slack user id (ie: U3216587)
     * @param boolean $private Determine if channels should be private (group) or public (channel)
     * @throws SlackApiException
     * @throws SlackConversationException
     * @return array
     */
    public function getUserConversations(string $slackId) : array
    {
        $memberOfChannels = [];
        $channels = $this->getConversations();

        // iterate over channels and check if the current slack user is part of channel
        foreach ($channels as $channel) {
            // exclude private chat (is_mpim) and main channel from result
            if (!$channel['is_mpim'] && !$channel['is_general']) {
                // search for Slack User ID into every channel members list
                // if we find it, append the channel id to the result
                $members = $this->getConversationMembers($channel['id']);
                if (in_array($slackId, $members)) {
                    $memberOfChannels[] = $channel['id'];
                }
            }
        }

        return $memberOfChannels;
    }

    /**
     * Get information from a specific channel
     *
     * @param string $channelId Slack channel id (ie: C465478)
     * @param boolean $private Determine if channels should be private (group) or public (channel)
     * @return array
     * @throws SlackApiException
     * @throws SlackConversationException
     */
    public function getConversationInfo(string $channelId) : array
    {
        $params = [
            'channel' => $channelId
        ];

        // The request is for a public channel, call channels endpoint
        $result = $this->post('/conversations.info', $params);

        // check that the request has been handled successfully. If not, fire an exception
        if (!$result['ok']) {
            throw new SlackConversationException($result['error']);
        }

        // return only channel array which contains information
        return $result['channel'];
    }

    /**
     * Invite an user into a specific channel
     *
     * @param string $slackId Slack user id (ie: U3216587)
     * @param string $channelId Slack channel id (ie: C6547987)
     * @throws SlackApiException
     * @return bool
     */
    public function inviteIntoConversation(string $slackId, string $channelId) : bool
    {
        // set parameters for Slack request, channel id and user id
        $params = [
            'channel' => $channelId,
            'users' => [$slackId],
        ];

        $result = $this->post('/conversations.invite', $params);

        return $result['ok'];
    }

    /**
     * Kick an user from a specific channel
     * 
     * @param string $slackId Slack user id (ie: U3216587)
     * @param string $channelId Slack channel id (ie: C6547987)
     * @param boolean $private Determine if channels should be private (group) or public (channel)
     * @throws SlackApiException
     * @return bool
     */
    public function kickFromConversion(string $slackId, string $channelId) : bool
    {
        // set parameters for Slack request, channel id and user id
        $params = [
            'channel' => $channelId,
            'user' => $slackId
        ];

        // We can't kick token owner himself
        if ($slackId == $this->tokenOwnerId) {
            return false;
        }

        // Retrieve channel information before kicking user
        $channel = $this->getConversationInfo($channelId);

        // If user is part of the channel and it's not the main channel, kick it
        if ($this->isMemberOf($slackId, $channelId) && !$channel['is_general']) {
            $result = $this->post('/conversations.kick', $params);
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
     * @throws SlackConversationException
     */
    public function getConversations(
        array $types = ['public_channel', 'private_channel'], string $cursor = null) : array
    {
        // we don't care from archived channels either they are public or private
        $params = [
            'exclude_archived' => 1,
            'cursor' => $cursor,
            'types' => implode(',', $types),
        ];

        // send request to Slack API and fetch result
        $result = $this->post('/conversations.list', $params);

        // check that the request has been handled successfully. If not, fire an exception
        if (!$result['ok']) {
            throw new SlackConversationException($result['error']);
        }

        $channels = $result['channels'];

        // recursive call in order to retrieve all paginated results
        if (array_key_exists('response_metadata', $result) && $result['response_metadata']['next_cursor'] != "") {
            $channels = array_merge($channels, $this->getConversations($params['types'], $result['response_metadata']['next_cursor']));
        }

        return $channels;
    }

    /**
     * Invite the token owner into a specific channel
     *
     * @param string $channelId Slack channel id (ie: C6547987)
     */
    public function joinConversation(string $channelId)
    {
        $params = [
            'channel' => $channelId,
        ];

        if (!$this->isMemberOf($this->tokenOwnerId, $channelId)) {
            $this->post('conversations.join', $params);
        }
    }

    /**
     * Return a list of members from a specific channel or groups
     *
     * @param string $channelId
     * @param string|null $cursor
     * @return array
     * @throws SlackConversationException
     */
    public function getConversationMembers(string $channelId, string $cursor = null) : array
    {
        $params = [
            'channel' => $channelId,
            'cursor' => $cursor,
        ];

        // send request to Slack API and fetch result
        $result = $this->post('/conversations.members', $params);

        // check that the request has been handled successfully. If not, fire an exception
        if (!$result['ok']) {
            throw new SlackConversationException($result['error']);
        }

        $members = $result['members'];

        if (array_key_exists('response_metadata', $result) && $result['response_metadata']['next_cursor'] != "") {
            $members = array_merge($members,
                $this->getConversationMembers($channelId, $result['response_metadata']['next_cursor']));
        }

        // return only members array which handle channels information like id or name
        return $members;
    }

    /**
     * Return a list of team members
     *
     * @return array
     * @throws SlackApiException
     * @throws SlackUserException
     */
    public function getTeamMembers(string $cursor = null) : array
    {
        $params = [
            'cursor' => $cursor,
        ];

        // send request to Slack API and fetch result
        $result = $this->post('/users.list', $params);

        // check that the request has been handled successfully. If not, fire an exception
        if (!$result['ok']) {
            throw new SlackUserException($result['error']);
        }

        $members = $result['members'];

        if (array_key_exists('response_metadata', $result) && $result['response_metadata']['next_cursor'] != "") {
            $members = array_merge($members,
                $this->getTeamMembers($result['response_metadata']['next_cursor']));
        }

        // return only members
        return $members;
    }

    public function getUserInfo($slackId)
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
        curl_setopt($curl, CURLOPT_USERAGENT, 'Seat-Slackbot/2.2.x mail=loic.leuilliot@gmail.com');

        // Slack is talking with us using JSON, fetch the result and convert into array
        $result = json_decode(curl_exec($curl), true);

        // check that the request has been received successfully. If not, fire an exception
        if ($result == null) {
            throw new SlackApiException("An error occurred while calling the Slack API\r\n" . curl_error($curl));
        }

        return $result;
    }
}