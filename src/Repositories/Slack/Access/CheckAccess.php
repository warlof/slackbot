<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 07/12/2017
 * Time: 22:43
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Access;


use Warlof\Seat\Slackbot\Repositories\Slack\Configuration;

class CheckAccess implements AccessInterface {

    protected $scope_map = [
        'get' => [
            '/auth.test'             => [],
            '/users.list'            => ['users:read', 'read'],
            '/users.info'            => ['users:read', 'read'],
            '/conversations.info'    => ['channels:read', 'groups:read', 'im:read', 'mpim:read', 'read'],
            '/conversations.list'    => ['channels:read', 'groups:read', 'im:read', 'mpim:read', 'read'],
            '/conversations.members' => ['channels:read', 'groups:read', 'im:read', 'mpim:read', 'read'],
        ],
        'post' => [
            '/conversations.invite'  => ['channels:write', 'groups:write', 'im:write', 'mpim:write', 'post'],
            '/conversations.kick'    => ['channels:write', 'groups:write', 'im:write', 'mpim:write', 'post'],
            '/conversations.join'    => ['channels:write', 'post'],
            '/users.lookupByEmail'   => ['users:read.email'],
        ],
    ];

    public function can(string $method, string $uri, array $scopes) : bool
    {
        if (!array_key_exists($uri, $this->scope_map[$method])) {
            Configuration::getInstance()->getLogger()
                ->warning('An unknown URI was called. Allowing ' . $uri);

            return true;
        }

        $required_scope = $this->scope_map[$method][$uri];

        if (array_diff($scopes, $required_scope) == $required_scope)
            return false;

        return true;
    }
}
