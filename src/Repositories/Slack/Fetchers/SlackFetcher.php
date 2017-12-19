<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 19/12/2017
 * Time: 12:52
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Fetchers;


use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\Exceptions\InvalidAuthencationException;
use Seat\Eseye\Exceptions\RequestFailedException;
use Warlof\Seat\Slackbot\Exceptions\SlackApiException;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackAuthentication;

class SlackFetcher extends \Seat\Eseye\Fetchers\GuzzleFetcher {

    public function __construct(SlackAuthentication $authentication = null ) {
        parent::__construct($authentication);

        $this->sso_base = 'https://slack.com/api';
    }

    /**
     * @return array
     * @throws InvalidAuthencationException
     * @throws RequestFailedException
     */
    public function getAuthenticationScopes() : array
    {
        if (count($this->getAuthentication()->scopes) < 1)
            $this->setAuthenticationScopes();

        return $this->getAuthentication()->scopes;
    }

    /**
     * @throws InvalidAuthencationException
     * @throws RequestFailedException
     */
    public function setAuthenticationScopes() {
        $scopes = $this->verifyToken()['X-OAuth-Scopes'];
        $this->authentication->scopes = explode(' ', $scopes);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param array $body
     *
     * @return EsiResponse
     * @throws RequestFailedException
     */
    public function httpRequest(string $method, string $uri, array $headers = [], array $body = []) : EsiResponse
    {
        $content = parent::httpRequest( $method, $uri, $headers, $body );

        if (!property_exists($content, 'ok'))
            return $content;

        if ($content->ok)
            return $content;

        $this->logger->error('[http ' . $content->getErrorCode() . '] ' .
            property_exists($content, 'error') ? '[' . $content->error . '] ' : '[errors] ' .
            $method . ' -> ' . $this->stripRefreshTokenValue($uri));

        throw new RequestFailedException(
            new SlackApiException('An error occured on API request. Please find detail in body.'),
            $content
        );
    }

    /**
     * @return mixed|EsiResponse
     * @throws InvalidAuthencationException
     * @throws RequestFailedException
     */
    private function verifyToken()
    {
        return $this->httpRequest('get', $this->sso_base . '/auth.test', [
            'Authorization' => 'Bearer ' . $this->getToken(),
        ]);
    }

    /**
     * @return string
     * @throws InvalidAuthencationException
     */
    private function getToken() : string
    {
        if(!$this->getAuthentication())
            throw new InvalidAuthencationException(
                'Trying to get a token without authentication data.');

        return $this->getAuthentication()->access_token;
    }

}
