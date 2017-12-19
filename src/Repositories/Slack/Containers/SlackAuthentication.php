<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 08/12/2017
 * Time: 21:29
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Containers;


use Warlof\Seat\Slackbot\Repositories\Slack\Containers\Traits\ConstructsContainers;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\Traits\ValidatesContainers;

class SlackAuthentication extends AbstractArrayAccess {

    use ConstructsContainers, ValidatesContainers;

    /**
     * @var array
     */
    protected $data = [
        'access_token'  => '_',
        'token_expires' => '1970-01-01 00:00:00',
        'scopes'        => [],
    ];

}
