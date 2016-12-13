<?php

/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 09/08/2016
 * Time: 16:25
 */

namespace Seat\Slackbot\Tests;

use Orchestra\Testbench\TestCase;
use Warlof\Seat\Slackbot\Commands\SlackChannelsUpdate;
use Warlof\Seat\Slackbot\Helpers\SlackApi;
use Warlof\Seat\Slackbot\Models\SlackChannel;

class SlackChannelsUpdateTest extends TestCase
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

    public function setUp()
    {
        parent::setUp();

        // setup Slack API
        $token = getenv('slack_token');

        app()->singleton('warlof.slackbot.slack', function() use ($token){
            return new SlackApi($token);
        });
    }

    public function testChannelUpdate()
    {
        // pre test
        setting(['slack_token', getenv('slack_token')], true);

        // test

        // get list of channels
        $channels = array_merge(
            app('warlof.slackbot.slack')->channels(false),
            app('warlof.slackbot.slack')->channels(true)
        );

        // store all channels in an array of object
        $artifacts = [];

        foreach ($channels as $c) {
            $artifacts[] = new SlackChannel([
                'id' => $c['id'],
                'name' => $c['name']
            ]);
        }

        // call slack:update:channels command
        $job = new SlackChannelsUpdate();
        $job->handle();

        // fetch in database channels
        $inDatabase = SlackChannel::all(['id', 'name']);

        // convert to an array of "new object"
        $result = [];

        foreach ($inDatabase as $object) {
            $result[] = new SlackChannel([
                'id' => $object->id,
                'name' => $object->name
            ]);
        }

        // compare both array
        $this->assertEquals($artifacts, $result);
    }

    /**
     * @expectedException Warlof\Seat\Slackbot\Exceptions\SlackSettingException
     */
    public function testTokenException()
    {
        // pre test
        setting(['slack_token', ''], true);

        // test
        $job = new SlackChannelsUpdate();
        $job->handle();
    }
}
