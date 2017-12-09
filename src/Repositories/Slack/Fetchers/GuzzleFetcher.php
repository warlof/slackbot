<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 08/12/2017
 * Time: 21:51
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Fetchers;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
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

	public function setAuthentication(SlackAuthentication $authentication)
	{
		if (!$authentication->valid())
			throw new InvalidAuthenticationException('Authentication data are invalid or empty.');

		$this->authentication = $authentication;
	}

	public function getAuthenticationScopes() : array
	{
		if (is_null($this->getAuthentication()))
			return ['public'];

		if (count($this->getAuthentication()->scopes) <= 0)
			$this->setAuthenticationScopes();

		return $this->getAuthentication()->scopes;
	}

	public function setAuthenticationScopes()
	{
		$scopes = $this->verifyToken()['Scopes'];
		$scopes .= ' public';
		$this->authentication->scopes = explode(' ', $scopes);
	}

	public function getToken() : string
	{
		if (!$this->getAuthentication())
			throw new InvalidAuthenticationException('Trying to get a token without authentication data.');

		$expires = carbon($this->getAuthentication()->token_expires);

		/* TODO : implement refreshToken
		if ($expires <= carbon('now')->addMinutes(5))
			$this->refreshToken();
		*/

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

	private function refreshToken()
	{
		$response = $this->httpRequest('post',
			$this->api_base . '/token?grant_type=refresh_token&refresh_token=' .
			$this->authentication->refresh_token, [
				'Authorization' => 'Basic ' . base64_encode($this->authentication->client_id . ':' . $this->authentication->secret),
			]);

		$authentication = $this->getAuthentication();

		$authentication->access_token = $response->access_token;
		$authentication->refresh_token = $response->refresh_token;
		$authentication->token_expires = carbon('now')->addSeconds($response->expires_in);

		$this->authentication = $authentication;
	}

	private function httpRequest(string $method, string $uri, array $headers = [], array $body = []) : SlackResponse
	{
		$headers = array_merge($headers, [
			'Accept' => 'application/json',
			'Content-Type' => 'application/json',
			'User-Agent' => 'Seat-Slackbot/' . SlackApi::VERSION . '/' . Configuration::getInstance()->http_user_agent,
		]);

		$this->logger->debug('Making ' . $method . ' request to ' . $uri);
		$start = microtime(true);

		if (count($body) > 0)
			$body = json_encode($body);
		else
			$body = null;

		try {
			$response = $this->getClient()->send(new Request($method, $uri, $headers, $body));
		} catch (ClientException $e) {
			$this->logger->error('[http ' . $e->getResponse()->getStatusCode() . '] ' .
				'[' . $e->getResponse()->getReasonPhrase() . '] ' .
				$method . ' -> ' . $this->stripRefreshTokenValue($uri) . ' [' .
				number_format(microtime(true) - $start, 2) . 's]');

			throw new RequestFailedException($e,
				$this->makeSlackResponse(
					(object) json_decode($e->getResponse()->getBody()), 'now',
					$e->getResponse()->getStatusCode()
				));
		}

		$content = (object) json_decode($response->getBody());
		if (property_exists($content, 'ok') && !$content->ok) {
			$this->logger->error('[http ' . $response->getStatusCode() . '] ' .
			                     (property_exists($content, 'error') ? '[' . $content->error . '] ' : '[errors] ').
				$method . ' -> ' . $this->stripRefreshTokenValue($uri) . ' [' .
				number_format(microtime(true) - $start, 2) . 's]');

			throw new RequestFailedException(
				new SlackApiException('An error occured on API request. Please find detail in body.'),
				$this->makeSlackResponse(
					$content, 'now',
					$response->getStatusCode()
				));
		}

		$this->logger->log('[http ' . $response->getStatusCode() . '] ' .
			'[' . $response->getReasonPhrase() . '] ' .
			$method . ' -> ' . $this->stripRefreshTokenValue($uri) . ' [' .
			number_format(microtime(true) - $start, 2) . 's]');

		return $this->makeSlackResponse(
			$content,
			$response->hasHeader('Expires') ? $response->getHeader('Expires')[0] : carbon('now')->addHour(),
			$response->getStatusCode()
		);
	}

	private function stripRefreshTokenValue(string $uri) : string
	{
		if (strpos($uri, 'refresh_token'))
			return Uri::withoutQueryValue((new Uri($uri)), 'refresh_token')->__toString();

		return $uri;
	}

	private function makeSlackResponse(stdClass $body, string $expires, int $status_code) : SlackResponse
	{
		return new SlackResponse($body, $expires, $status_code);
	}

	private function verifyToken()
	{
		return $this->httpRequest('get', $this->api_base . '/auth.test', [
			'Authorization' => 'Bearer ' . $this->getToken(),
		]);
	}
}
