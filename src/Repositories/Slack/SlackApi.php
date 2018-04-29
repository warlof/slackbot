<?php
/**
 * This file is part of slackbot and provide user synchronization between both SeAT and a Slack Team
 *
 * Copyright (C) 2016, 2017, 2018  Loïc Leuilliot <loic.leuilliot@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Warlof\Seat\Slackbot\Repositories\Slack\Access\AccessInterface;
use Warlof\Seat\Slackbot\Repositories\Slack\Access\CheckAccess;
use Warlof\Seat\Slackbot\Repositories\Slack\Cache\CacheInterface;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackAuthentication;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackResponse;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidAuthenticationException;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException;
use Warlof\Seat\Slackbot\Repositories\Slack\Fetchers\FetcherInterface;
use Warlof\Seat\Slackbot\Repositories\Slack\Log\LogInterface;

class SlackApi {

    protected $authentication;

    protected $fetcher;

    protected $cache;

    protected $access_checker;

    protected $query_string = [];

    protected $request_body = [];

    protected $api = [
        'scheme' => 'https',
        'host'   => 'slack.com/api',
    ];

    public function __construct(SlackAuthentication $authentication = null) {
        if (!is_null($authentication))
            $this->authentication = $authentication;

        return $this;
    }

    /**
     * @return Configuration
     * @throws InvalidContainerDataException
     */
    public function getConfiguration() : Configuration
    {
        return Configuration::getInstance();
    }

    /**
     * @return SlackAuthentication
     * @throws InvalidAuthenticationException
     */
    public function getAuthentication() : SlackAuthentication
    {
        if (is_null($this->authentication))
            throw new InvalidAuthenticationException('Authentication data not set.');

        return $this->authentication;
    }

    /**
     * @param SlackAuthentication $authentication
     *
     * @return SlackApi
     * @throws InvalidContainerDataException
     */
    public function setAuthentication(SlackAuthentication $authentication) : self
    {
        if (!$authentication->valid())
            throw new InvalidContainerDataException('Authentication data are invalid or empty');

        $this->authentication = $authentication;

        return $this;
    }

    /**
     * @return FetcherInterface
     * @throws InvalidContainerDataException
     */
    public function getFetcher() : FetcherInterface
    {
        if (!$this->fetcher) {
            $fetcher_class = $this->getConfiguration()->fetcher;
            $this->fetcher = new $fetcher_class(...[$this->authentication]);
        }

        return $this->fetcher;
    }

    public function setFetcher(FetcherInterface $fetcher)
    {
        $this->fetcher = $fetcher;
    }

    /**
     * @return LogInterface
     * @throws InvalidContainerDataException
     */
    public function getLogger() : LogInterface
    {
        return $this->getConfiguration()->getLogger();
    }

    /**
     * @return CacheInterface
     * @throws InvalidContainerDataException
     */
    public function getCache() : CacheInterface
    {
        return $this->getConfiguration()->getCache();
    }

    public function getAccessChecker() : CheckAccess
    {
        if (!$this->access_checker)
            $this->access_checker = new CheckAccess();

        return $this->access_checker;
    }

    public function setAccessChecker(AccessInterface $checker) : self
    {
        $this->access_checker = $checker;

        return $this;
    }

    public function getQueryString() : array
    {
        return $this->query_string;
    }

    public function setQueryString(array $query) : self
    {
        $this->query_string = $query;

        return $this;
    }

    public function getBody() : array
    {
        return $this->request_body;
    }

    public function setBody(array $body) : self
    {
        $this->request_body = $body;

        return $this;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $uri_data
     * @return SlackResponse
     * @throws InvalidContainerDataException
     * @throws RequestFailedException
     * @throws SlackScopeAccessDeniedException
     * @throws UriDataMissingException
     */
    public function invoke(string $method, string $uri, array $uri_data = []) : SlackResponse
    {
        if (!$this->getAccessChecker()->can($method, $uri, $this->getFetcher()->getAuthenticationScopes())) {
            $uri = $this->buildDataUri($uri, $uri_data);

            $this->getLogger()->error('Access denied to ' . $uri . ' due to missing scopes.');

            throw new SlackScopeAccessDeniedException('Access denied to ' . $uri);
        }

        $uri = $this->buildDataUri($uri, $uri_data);
        $this->setQueryString([]);

        $result = $this->rawFetch($method, $uri, $this->getBody());

        $this->setBody([]);

        return $result;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $body
     * @return SlackResponse
     * @throws InvalidContainerDataException
     * @throws RequestFailedException
     */
    private function rawFetch(string $method, string $uri, array $body) : SlackResponse
    {
        return $this->getFetcher()->call($method, $uri, $body);
    }

    /**
     * @param string $uri
     * @param array $data
     *
     * @return UriInterface
     * @throws UriDataMissingException
     */
    private function buildDataUri(string $uri, array $data) : UriInterface
    {
        $query_params = $this->getQueryString();

        return Uri::fromParts([
            'scheme' => $this->api['scheme'],
            'host'   => $this->api['host'],
            'path'   => $this->mapDataToUri($uri, $data),
            'query'  => http_build_query($query_params),
        ]);
    }

    /**
     * @param string $uri
     * @param array $data
     *
     * @return string
     * @throws UriDataMissingException
     */
    private function mapDataToUri(string $uri, array $data) : string
    {
        if (preg_match('/{+(.*?)}/', $uri, $matches)) {
            if (empty($data))
                throw new UriDataMissingException(
                    'The data array for the uri ' . $uri . ' is empty. Please provide data to use.');

            foreach ($matches[1] as $field) {
                if (!array_key_exists($field, $data))
                    throw new UriDataMissingException(
                        'Data for ' . $field . ' is missing. Please provide this by setting a value ' .
                        'for ' . $field . '.');

                $uri = str_replace('{' . $field . '}', $data[$field], $uri);
            }
        }

        return $uri;
    }
}