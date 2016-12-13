<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 10/08/2016
 * Time: 14:52
 */

namespace Warlof\Seat\Slackbot\Tests;


use Orchestra\Testbench\TestCase;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Models\SlackChannelRole;
use Seat\Web\Models\Acl\Role;

class SlackChannelRoleTest extends TestCase
{
    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => getenv('database_host'),
            'database' => getenv('database_name'),
            'username' => getenv('database_user'),
            'password' => getenv('database_pass'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => ''
        ]);
    }

    public function testChannel()
    {
        $permission = SlackChannelRole::where('role_id', '=', 1)->first();
        $artifact = SlackChannel::find('C1Z920QKC');

        $this->assertEquals($artifact, $permission->channel);
    }

    public function testRole()
    {
        $permission = SlackChannelRole::where('role_id', '=', 1)->first();
        $artifact = Role::find(1);

        $this->assertEquals($artifact, $permission->role);
    }
}
