<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 10/08/2016
 * Time: 14:26
 */

namespace Seat\Slackbot\Tests;


use Orchestra\Testbench\TestCase;
use Seat\Eveapi\Models\Corporation\CorporationSheet;
use Seat\Slackbot\Models\SlackChannel;
use Seat\Slackbot\Models\SlackChannelCorporation;

class SlackChannelCorporationTest extends TestCase
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
        $permission = SlackChannelCorporation::where('corporation_id', '=', 98413060)->first();
        $artifact = SlackChannel::find('C1Z920QKC');

        $this->assertEquals($artifact, $permission->channel);
    }

    public function testCorporation()
    {
        $permission = SlackChannelCorporation::where('corporation_id', '=', 98413060)->first();
        $artifact = CorporationSheet::find(98413060);

        $this->assertEquals($artifact, $permission->corporation);
    }
}
