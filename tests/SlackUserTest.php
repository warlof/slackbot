<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 10/08/2016
 * Time: 14:55
 */

namespace Seat\Slackbot\Tests;


use Orchestra\Testbench\TestCase;
use \Warlof\Seat\Slackbot\Models\SlackUser;
use Seat\Web\Models\User;

class SlackUserTest extends TestCase
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

    public function testUser()
    {
        $slack = SlackUser::where('user_id', '=', 3)->first();
        $artifact = User::find(3);

        $this->assertEquals($artifact, $slack->user);
    }
}
