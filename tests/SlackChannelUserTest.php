<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 10/08/2016
 * Time: 11:30
 */

namespace Seat\Slackbot\Tests;


use Orchestra\Testbench\TestCase;
use Seat\Slackbot\Models\SlackChannel;
use Seat\Slackbot\Models\SlackChannelUser;
use Seat\Web\Models\User;

class SlackChannelUserTest extends TestCase
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
        $permission = SlackChannelUser::where('user_id', '=', 1)->first();
        $artifact = SlackChannel::find('C1Z920QKC');

        $this->assertEquals($artifact, $permission->channel);
    }

    public function testUser()
    {
        $permission = SlackChannelUser::where('user_id', '=', 1)->first();
        $artifact = User::find(1);

        $this->assertEquals($artifact, $permission->user);
    }
}
