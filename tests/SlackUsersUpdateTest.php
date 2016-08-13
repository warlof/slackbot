<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 10/08/2016
 * Time: 09:29
 */

namespace Seat\Slackbot\Tests;


use Orchestra\Testbench\TestCase;
use Seat\Services\Settings\Seat;
use Seat\Slackbot\Commands\SlackUsersUpdate;
use Seat\Slackbot\Helpers\SlackApi;
use Seat\Slackbot\Models\SlackUser;

class SlackUsersUpdateTest extends TestCase
{
    /**
     * @var SlackApi
     */
    private $slackApi;

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

        $this->slackApi = new SlackApi($token);
    }

    public function testUserUpdate()
    {
        // pre test
        Seat::set('slack_token', getenv('slack_token'));

        $artifacts = [new SlackUser(['user_id' => 1, 'slack_id' => 'U1Z9LT9NM']),
            new SlackUser(['user_id' => 2, 'slack_id' => 'U1Z9QVCJW']),
            new SlackUser(['user_id' => 3, 'slack_id' => 'U1Z9LT9NK'])];

        // test
        $job = new SlackUsersUpdate();
        $job->handle();

        $inDatabaseMember  = SlackUser::all(['user_id', 'slack_id']);

        $result = [];

        foreach ($inDatabaseMember as $member) {
            $result[] = new SlackUser([
                'user_id' => $member->user_id,
                'slack_id' => $member->slack_id
            ]);
        }

        // compare both array
        $this->assertEquals($artifacts, $result);
    }

    /**
     * @expectedException Seat\Slackbot\Exceptions\SlackSettingException
     */
    public function testTokenException()
    {
        // pre test
        Seat::set('slack_token', '');

        // test
        $job = new SlackUsersUpdate();
        $job->handle();
    }
}
