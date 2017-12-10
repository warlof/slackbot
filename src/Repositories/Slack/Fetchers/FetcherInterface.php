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
    public function call(string $method, string $uri, array $body, array $headers = []) : SlackResponse;

    public function getAuthenticationScopes() : array;
}
