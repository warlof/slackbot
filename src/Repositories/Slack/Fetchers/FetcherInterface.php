<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 08/12/2017
 * Time: 21:50
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Fetchers;


use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackResponse;

interface FetcherInterface
{
    /**
     * @param string $method
     * @param string $uri
     * @param array $body
     * @param array $headers
     *
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
     * @return SlackResponse
     */
    public function call(string $method, string $uri, array $body, array $headers = []) : SlackResponse;

    public function getAuthenticationScopes() : array;
}
