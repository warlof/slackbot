<?php

/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 09/08/2016
 * Time: 16:25
 */

namespace Seat\Slackbot\Tests;

use Orchestra\Testbench\TestCase;
use Warlof\Seat\Slackbot\Commands\SlackLogsClear;
use Warlof\Seat\Slackbot\Models\SlackLog;

class SlackLogsClearTest extends TestCase
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

    public function testLogsClear()
    {
        $logAmount = SlackLog::all()->count();
        $this->assertGreaterThan(0, $logAmount);

        $command = new SlackLogsClear();
        $command->handle();

        $logAmount = SlackLog::all()->count();

        // compare both array
        $this->assertEquals(0, $logAmount);
    }
}
