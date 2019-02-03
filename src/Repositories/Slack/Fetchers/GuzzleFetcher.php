<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015, 2016, 2017, 2018, 2019  Leon Jacobs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Fetchers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use Warlof\Seat\Slackbot\Exceptions\SlackApiException;
use Warlof\Seat\Slackbot\Repositories\Slack\Configuration;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackAuthentication;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackResponse;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidAuthenticationException;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException;

class GuzzleFetcher implements FetcherInterface
{

    /**
     * @var null|SlackAuthentication
     */
    protected $authentication;

    /**
     * @var
     */
    protected $client;

    /**
     * @var \Warlof\Seat\Slackbot\Repositories\Slack\Log\LogInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $api_base = 'https://slack.com/api';

    /**
     * GuzzleFetcher constructor.
     * @param SlackAuthentication|null $authentication
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     */
    public function __construct(SlackAuthentication $authentication = null)
    {
        $this->authentication = $authentication;
        $this->logger = Configuration::getInstance()->getLogger();
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $body
     * @param array $headers
     * @return SlackResponse
     * @throws InvalidAuthenticationException
     * @throws RequestFailedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     */
    public function call(string $method, string $uri, array $body, array $headers = []) : SlackResponse
    {
        if ($this->getAuthentication())
            $headers = array_merge($headers, [
                'Authorization' => 'Bearer ' . $this->getToken(),
            ]);

        return $this->httpRequest($method, $uri, $headers, $body);
    }

    /**
     * @return null|SlackAuthentication
     */
    public function getAuthentication()
    {
        return $this->authentication;
    }

    /**
     * @param SlackAuthentication $authentication
     *
     * @throws InvalidAuthenticationException
     */
    public function setAuthentication(SlackAuthentication $authentication)
    {
        if (!$authentication->valid())
            throw new InvalidAuthenticationException('Authentication data are invalid or empty.');

        $this->authentication = $authentication;
    }

    /**
     * @return array
     * @throws InvalidAuthenticationException
     * @throws RequestFailedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     */
    public function getAuthenticationScopes() : array
    {
        if (is_null($this->getAuthentication()))
            return ['public'];

        if (count($this->getAuthentication()->scopes) <= 0)
            $this->setAuthenticationScopes();

        return $this->getAuthentication()->scopes;
    }

    /**
     * @throws InvalidAuthenticationException
     * @throws RequestFailedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     */
    public function setAuthenticationScopes()
    {
        $scopes = $this->verifyToken()['Scopes'];
        $scopes .= ' public';
        $this->authentication->scopes = explode(' ', $scopes);
    }

    /**
     * @return string
     * @throws InvalidAuthenticationException
     */
    public function getToken() : string
    {
        if (!$this->getAuthentication())
            throw new InvalidAuthenticationException('Trying to get a token without authentication data.');

        return $this->getAuthentication()->access_token;
    }

    /**
     * @return Client
     */
    public function getClient() : Client
    {
        if (!$this->client)
            $this->client = new Client();

        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param array $body
     * @return SlackResponse
     * @throws RequestFailedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     */
    private function httpRequest(string $method, string $uri, array $headers = [], array $body = []) : SlackResponse
    {
        $request_body = null;

        $headers = array_merge($headers, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json;charset=utf-8',
            'User-Agent' => 'Seat-Slackbot/' . config('slackbot.config.version') . '/' . Configuration::getInstance()->http_user_agent,
        ]);

        $start = microtime(true);

        if (count($body) > 0)
            $request_body = json_encode($body);

        try {

            $response = $this->getClient()->send(new Request($method, $uri, $headers, $request_body));

        } catch (ClientException $e) {

            $requestTime = number_format(microtime(true) - $start, 2);
            $content = (object) json_decode($e->getResponse()->getBody());

            $this->logRequest('error', $method, $uri, $request_body, $e->getResponse(), $content, $requestTime);

            if ($e->getResponse()->getStatusCode() == 429) {
                // Apply cool-down
                $requestedCalmDown = $e->getResponse()->getHeaderLine('Retry-After');
                if ($requestedCalmDown == null | $requestedCalmDown == '')
                    $requestedCalmDown = 1;

                sleep((int) $requestedCalmDown);
                // Make a new attempt
                return $this->httpRequest($method, $uri, is_null($headers) ? [] : $headers, is_null($request_body) ? [] : $request_body);
            }

            throw new RequestFailedException($e,
                $this->makeSlackResponse($content, 'now', $e->getResponse()->getStatusCode()));

        } catch(ServerException $e) {

            $requestTime = number_format(microtime(true) - $start, 2);

            $content = (object) json_decode($e->getResponse()->getBody());
            $this->logRequest('error', $method, $uri, $request_body, $e->getResponse(), $content, $requestTime);

            throw new RequestFailedException($e,
                $this->makeSlackResponse($content, 'now', $e->getResponse()->getStatusCode()));
        }

        $content = (object) json_decode($response->getBody());
        $requestTime = number_format(microtime(true) - $start, 2);

        if (property_exists($content, 'ok') && !$content->ok) {
            $this->logRequest('warning', $method, $uri, $request_body, $response, $content, $requestTime);

            throw new RequestFailedException(
                new SlackApiException('An error occurred on API request. Please find detail in body.'),
                $this->makeSlackResponse($content, 'now', $response->getStatusCode()));
        }

        $this->logRequest('debug', $method, $uri, $request_body, $response, $content, $requestTime);

        return $this->makeSlackResponse($content,
            $response->hasHeader('Expires') ? $response->getHeader('Expires')[0] : 'now',
            $response->getStatusCode());
    }

    /**
     * @param string $level
     * @param string $method
     * @param string $uri
     * @param string $request_body
     * @param ResponseInterface $response
     * @param $response_body
     * @param $request_time
     */
    private function logRequest(string $level, string $method, string $uri, string $request_body = null, ResponseInterface $response, $response_body, $request_time)
    {
        $level = strtolower($level);
        $method = strtoupper($method);

        $allowed_level = ['debug', 'error', 'warning', 'log'];
        $allowed_method = ['GET', 'POST', 'HEAD', 'PATCH', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE'];

        if (! in_array($level, $allowed_level))
            throw new InvalidArgumentException(sprintf('$level must have one of the following value (%s)',
                implode(', ', $allowed_level)));

        if (! in_array($method, $allowed_method))
            throw new InvalidArgumentException(sprintf('$method must have one of the following value (%s)',
                implode(', ', $allowed_method)));

        $this->logger->$level(sprintf('[http %s] [%s] %s -> %s [%F]',
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            $method,
            $this->sanitizeUri($uri),
            $request_time), $this->getResponseContext($uri, $request_body, $response, $response_body, $request_time));
    }

    /**
     * @param string $uri
     * @param string $request_body
     * @param ResponseInterface $response
     * @param $response_body
     * @param $request_time
     * @return array
     */
    private function getResponseContext(string $uri, string $request_body = null, ResponseInterface $response, $response_body, $request_time)
    {
        return [
            'code' => $response->getStatusCode(),
            'uri'  => $uri,
            'Request-Body' => is_null($request_body) ? null : $request_body,
            'Versions' => [
                'slackbot' => config('slackbot.config.version'),
            ],
            'Request-ID' => $response->getHeaderLine('X-Slack-Req-Id'),
            'Request-Time' => $request_time,
            'Headers' => $response->getHeaders(),
            'Body' => json_encode($response_body),
        ];
    }

    /**
     * @param string $uri
     * @return string
     */
    private function sanitizeUri(string $uri) : string
    {
        $uri = new Uri($uri);
        return $uri->getScheme() . '://' . $uri->getHost() . (Uri::isDefaultPort($uri) ? '' : ':' . $uri->getPort()) . $uri->getPath();
    }

    /**
     * @param stdClass $body
     * @param string $expires
     * @param int $status_code
     * @return SlackResponse
     */
    private function makeSlackResponse(stdClass $body, string $expires, int $status_code) : SlackResponse
    {
        return new SlackResponse($body, $expires, $status_code);
    }

    /**
     * @return SlackResponse
     * @throws InvalidAuthenticationException
     * @throws RequestFailedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     */
    private function verifyToken()
    {
        return $this->httpRequest('get', $this->api_base . '/auth.test', [
            'Authorization' => 'Bearer ' . $this->getToken(),
        ]);
    }
}
