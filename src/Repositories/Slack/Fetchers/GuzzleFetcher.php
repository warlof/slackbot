<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 08/12/2017
 * Time: 21:51
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
use Warlof\Seat\Slackbot\Repositories\Slack\SlackApi;

class GuzzleFetcher implements FetcherInterface
{
    protected $authentication;

    protected $client;

    protected $logger;

    protected $api_base = 'https://slack.com/api';

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
     *
     * @return SlackResponse
     * @throws InvalidAuthenticationException
     * @throws RequestFailedException
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
     *
     * @return SlackResponse
     * @throws RequestFailedException
     */
    private function httpRequest(string $method, string $uri, array $headers = [], array $body = []) : SlackResponse
    {
        $headers = array_merge($headers, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
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
                'Versions' => [
                    'slackbot' => config('slackbot.config.version'),
                ],
                'Request-ID' => $response->getHeaderLine('X-Slack-Req-Id'),
                'Request-Time' => $requestTime,
                'Body' => $content,
            ]);

            throw new RequestFailedException(
                new SlackApiException('An error occured on API request. Please find detail in body.'),
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

    private function stripRefreshTokenValue(string $uri) : string
    {
        if (strpos($uri, 'refresh_token'))
            return Uri::withoutQueryValue((new Uri($uri)), 'refresh_token')->__toString();

        return $uri;
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
     */
    private function verifyToken()
    {
        return $this->httpRequest('get', $this->api_base . '/auth.test', [
            'Authorization' => 'Bearer ' . $this->getToken(),
        ]);
    }
}
