<?php

/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 09/08/2016
 * Time: 16:25
 */

namespace Seat\Slackbot\Tests;

use Orchestra\Testbench\TestCase;
use Warlof\Seat\Slackbot\Commands\SlackChannelsUpdate;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Repositories\SlackApi;

class SlackChannelsUpdateTest extends TestCase
{

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'redis');
        $app['config']->set('cache.prefix', 'seat');
        $app['config']->set('cache.stores.redis', [
            'driver' => 'redis',
            'connection' => 'default',
        ]);
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => getenv('database_host'),
            'database' => getenv('database_name'),
            'username' => getenv('database_user'),
            'password' => getenv('database_pass'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ]);
    }

    public function setUp()
    {
        parent::setUp();

        // setup Slack API
        $token = getenv('slack_token');

        app()->singleton(SlackApi::class, function() use ($token){
            return new SlackApi($token);
        });
    }

    public function testChannelUpdate()
    {
        // pre test
        setting(['warlof.slackbot.credentials.access_token', getenv('slack_token')], true);

        // test

        // get list of channels
        $channels = app(SlackApi::class)->getConversations();

        // store all channels in an array of object
        $artifacts = [];

        foreach ($channels as $c) {
            $artifacts[] = new SlackChannel([
                'id' => $c['id'],
                'name' => $c['name']
            ]);
        }

        // set random cache key
        Redis::set('seat:warlof:slackbot:conversations.C65464654', 'test');

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
        setting(['warlof.slackbot.credentials.access_token', ''], true);

        // test
        $job = new SlackChannelsUpdate();
        $job->handle();
    }
}
