<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 19/12/2017
 * Time: 10:43
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack;


use GuzzleHttp\Psr7\Uri;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\Eseye;

class SlackDriver extends Eseye {

    public function __construct(EsiAuthentication $authentication) {
        parent::__construct($authentication);

        $this->esi = [
            'scheme' => 'https',
            'host'   => 'slack.com/api',
        ];
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $uri_data
     *
     * @return EsiResponse
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     */
    public function invoke(string $method, string $uri, array $uri_data = []) : EsiResponse
    {
        $result = parent::invoke($method, $uri, $uri_data);

        // reset body and query string
        $this->setBody([]);
        $this->setQueryString([]);

        return $result;
    }

    public function buildDataUri( string $uri, array $data ) : Uri
    {

        return Uri::fromParts([
            'scheme' => $this->api['scheme'],
            'host'   => $this->api['host'],
            'path'   => $uri,
            'query'  => http_build_query($this->getQueryString()),
        ]);

    }
}
