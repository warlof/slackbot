<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 10/08/2016
 * Time: 11:49
 */

namespace Warlof\Seat\Slackbot\Tests;


use Orchestra\Testbench\TestCase;
use Seat\Eveapi\Models\Eve\AllianceList;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Models\SlackChannelAlliance;

class SlackChannelAllianceTest extends TestCase
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
        $permission = SlackChannelAlliance::where('alliance_id', '=', 99000006)->first();
        $artifact = SlackChannel::find('C1Z920QKC');

        $this->assertEquals($artifact, $permission->channel);
    }

    public function testAlliance()
    {
        $permission = SlackChannelAlliance::where('alliance_id', '=', 99000006)->first();
        $artifact = AllianceList::find(99000006);

        $this->assertEquals($artifact, $permission->alliance);
    }
}
