<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015, 2016, 2017  Leon Jacobs
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
use stdClass;
use Warlof\Seat\Slackbot\Exceptions\SlackApiException;
use Warlof\Seat\Slackbot\Repositories\Slack\Configuration;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackAuthentication;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackResponse;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidAuthenticationException;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException;

class GuzzleFetcher implements FetcherInterface
{
    protected $authentication;

    protected $client;

    protected $logger;

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

    public function getClient() : Client
    {
        if (!$this->client)
            $this->client = new Client();

        return $this->client;
    }

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
        $headers = array_merge($headers, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json;charset=utf-8',
            'User-Agent' => 'Seat-Slackbot/' . config('slackbot.config.version') . '/' . Configuration::getInstance()->http_user_agent,
        ]);

        $start = microtime(true);

        if (count($body) > 0)
            $body = json_encode($body);
        else
            $body = null;

        try {
            $response = $this->getClient()->send(new Request($method, $uri, $headers, $body));
        } catch (ClientException $e) {
            $requestTime = number_format(microtime(true) - $start, 2);

            $this->logger->error(
                sprintf('[http %s] [%s] %s -> %s [%F]',
                    $e->getResponse()->getStatusCode(),
                    $e->getResponse()->getReasonPhrase(),
                    strtoupper($method),
                    $this->sanitizeUri($uri),
                    $requestTime), [
                'code' => $e->getResponse()->getStatusCode(),
                'uri' => $uri,
                'Request-Body' => (is_null($body)) ? null : json_encode($body),
                'Versions' => [
                    'slackbot' => config('slackbot.config.version'),
                ],
                'Request-ID' => $e->getResponse()->getHeaderLine('X-Slack-Req-Id'),
                'Request-Time' =>$requestTime,
                'Headers' => $e->getResponse()->getHeaders(),
                'Body' => $e->getResponse()->getBody(),
            ]);

            if ($e->getResponse()->getStatusCode() == 429) {
                // Apply cool-down
                $requestedCalmDown = $e->getResponse()->getHeaderLine('Retry-After');
                if ($requestedCalmDown == null | $requestedCalmDown == '')
                    $requestedCalmDown = 1;

                sleep((int) $requestedCalmDown);
                // Make a new attempt
                return $this->httpRequest($method, $uri, is_null($headers) ? [] : $headers, is_null($body) ? [] : $body);
            }

            throw new RequestFailedException($e,
                $this->makeSlackResponse(
                    (object) json_decode($e->getResponse()->getBody()), 'now',
                    $e->getResponse()->getStatusCode()
                ));
        } catch(ServerException $e) {
            $requestTime = number_format(microtime(true) - $start, 2);

            $this->logger->error(
                sprintf('[http %s] [%s] %s -> %s [%F]',
                    $e->getResponse()->getStatusCode(),
                    $e->getResponse()->getReasonPhrase(),
                    strtoupper($method),
                    $this->sanitizeUri($uri),
                    $requestTime), [
                'code' => $e->getResponse()->getStatusCode(),
                'uri' => $uri,
                'Request-Body' => (is_null($body)) ? null : json_encode($body),
                'Versions' => [
                    'slackbot' => config('slackbot.config.version'),
                ],
                'Request-ID' => $e->getResponse()->getHeaderLine('X-Slack-Req-Id'),
                'Request-Time' => $requestTime,
                'Headers' => $e->getResponse()->getHeaders(),
                'Body' => $e->getResponse()->getBody(),
            ]);

            throw new RequestFailedException($e,
                $this->makeSlackResponse(
                    (object) json_decode($e->getResponse()->getBody()), 'now',
                    $e->getResponse()->getStatusCode()
                ));
        }

        $content = (object) json_decode($response->getBody());
        $requestTime = number_format(microtime(true) - $start, 2);

        if (property_exists($content, 'ok') && !$content->ok) {
            $this->logger->warning(
                sprintf('[http %s] [%s] %s -> %s [%F]',
                    $response->getStatusCode(),
                    $response->getReasonPhrase(),
                    strtoupper($method),
                    $this->sanitizeUri($uri),
                    $requestTime), [
                'code' => $response->getStatusCode(),
                'uri' => $uri,
                'Request-Body' => (is_null($body)) ? null : json_encode($body),
                'Versions' => [
                    'slackbot' => config('slackbot.config.version'),
                ],
                'Request-ID' => $response->getHeaderLine('X-Slack-Req-Id'),
                'Request-Time' => $requestTime,
                'Body' => $content,
            ]);

            throw new RequestFailedException(
                new SlackApiException('An error occurred on API request. Please find detail in body.'),
                $this->makeSlackResponse(
                    $content, 'now',
                    $response->getStatusCode()
                ));
        }

        $this->logger->debug(
            sprintf('[http %s] [%s] %s -> %s [%s]',
                $response->getStatusCode(),
                $response->getReasonPhrase(),
                strtoupper($method),
                $this->sanitizeUri($uri),
                $requestTime), [
            'code' => $response->getStatusCode(),
            'uri' => $uri,
            'Request-Body' => (is_null($body)) ? null : json_encode($body),
            'Versions' => [
                'slackbot' => config('slackbot.config.version'),
            ],
            'Request-ID' => $response->getHeaderLine('X-Slack-Req-Id'),
            'Request-Time' => $requestTime,
            'Body' => $content,
        ]);

        return $this->makeSlackResponse(
            $content,
            $response->hasHeader('Expires') ? $response->getHeader('Expires')[0] : 'now',
            $response->getStatusCode()
        );
    }

    private function sanitizeUri(string $uri) : string
    {
        $uri = new Uri($uri);
        return $uri->getScheme() . '://' . $uri->getHost() . (Uri::isDefaultPort($uri) ? '' : ':' . $uri->getPort()) . $uri->getPath();
    }

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
