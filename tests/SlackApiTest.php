<?php

/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 08/08/2016
 * Time: 17:07
 */

namespace Warlof\Seat\Slackbot\Tests;

use Orchestra\Testbench\TestCase;
use Warlof\Seat\Slackbot\Repositories\SlackApi;

class SlackApiTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();

        $token = getenv('slack_token');

        app()->singleton('warlof.slackbot.slack', function() use ($token){
            return new SlackApi($token);
        });
    }

    /**
     * @expectedException Warlof\Seat\Slackbot\Exceptions\SlackMailException
     */
    public function testInviteMailException()
    {
        $mail = "example@domain.local";

        app('warlof.slackbot.slack')->inviteToTeam($mail);
    }

    /**
     * @expectedException Warlof\Seat\Slackbot\Exceptions\SlackTeamInvitationException
     */
    public function testInviteTeamException()
    {
        $mail = "e.elfaus@gmail.com";

        app('warlof.slackbot.slack')->inviteToTeam($mail);
    }

    public function testMemberPublicChannel()
    {
        $slackUserId = "U1Z8TCZAT";
        $slackChannelsId = ["C1Z920QKC"];
        $apiResponse = app('warlof.slackbot.slack')->memberOf($slackUserId, false);

        $this->assertEquals($slackChannelsId, $apiResponse);
    }

    public function testMemberPrivateChannel()
    {
        $slackUserId = "U1Z8TCZAT";
        $slackChannelsId = ["G1Z9267L1", "G1Z9CBCP8", "G1ZUXJZSL"];
        $apiResponse = app('warlof.slackbot.slack')->memberOf($slackUserId, true);

        $this->assertEquals($apiResponse, $slackChannelsId);
    }

    public function testInfoPublicChannel()
    {
        $slackChannelId = "C1Z8J1BFY";
        $apiResponse = app('warlof.slackbot.slack')->info($slackChannelId, false);
        $artifact = $this->getArtifactPath("public_info.json");

        $this->assertJsonStringEqualsJsonFile($artifact, json_encode($apiResponse));
    }

    public function testInfoPrivateChannel()
    {
        $slackChannelId = "G1ZUXJZSL";
        $apiResponse = app('warlof.slackbot.slack')->info($slackChannelId, true);
        $artifact = $this->getArtifactPath("private_info.json");

        $this->assertJsonStringEqualsJsonFile($artifact, json_encode($apiResponse));
    }

    /**
     * @expectedException Warlof\Seat\Slackbot\Exceptions\SlackChannelException
     */
    public function testInfoChannelException()
    {
        $slackChannelId = "C2Z4D897";

        app('warlof.slackbot.slack')->info($slackChannelId, false);
    }

    /**
     * @expectedException Warlof\Seat\Slackbot\Exceptions\SlackGroupException
     */
    public function testInfoGroupException()
    {
        $slackChannelId = "C2Z4D897";

        app('warlof.slackbot.slack')->info($slackChannelId, true);
    }

    public function testInvitePublicChannel()
    {
        $slackUserId = "U1Z9LT9NK";
        $slackChannelId = "C1Z920QKC";

        $this->assertNull(app('warlof.slackbot.slack')->invite($slackUserId, $slackChannelId, false));
    }

    public function testInvitePrivateChannel()
    {
        $slackUserId = "U1Z9LT9NK";
        $slackChannelId = "G1Z9267L1";

        $this->assertNull(app('warlof.slackbot.slack')->invite($slackUserId, $slackChannelId, true));
    }

    /**
     * @expectedException Warlof\Seat\Slackbot\Exceptions\SlackChannelException
     */
    public function testInvitePublicChannelException()
    {
        $slackUserId = "U1Z9LT9NP";
        $slackChannelId = "C1Z920QKC";

        app('warlof.slackbot.slack')->invite($slackUserId, $slackChannelId, false);
    }

    /**
     * @expectedException Warlof\Seat\Slackbot\Exceptions\SlackGroupException
     */
    public function testInvitePrivateChannelException()
    {
        $slackUserId = "U1Z9LT9NP";
        $slackChannelId = "G1Z9267L1";

        app('warlof.slackbot.slack')->invite($slackUserId, $slackChannelId, true);
    }

    public function testKickPublicChannel()
    {
        $slackUserId = "U1Z9LT9NK";
        $slackChannelId = "C1Z920QKC";

        $this->assertNull(app('warlof.slackbot.slack')->kick($slackUserId, $slackChannelId, false));
    }

    public function testKickPrivateChannel()
    {
        $slackUserId = "U1Z9LT9NK";
        $slackChannelId = "G1Z9267L1";

        $this->assertNull(app('warlof.slackbot.slack')->kick($slackUserId, $slackChannelId, true));
    }

    /**
     * @expectedException Warlof\Seat\Slackbot\Exceptions\SlackChannelException
     */
    public function testKickPublicChannelException()
    {
        $slackUserId = "U1Z8TCZA3";
        $slackChannelId = "G1Z920QKC";

        app('warlof.slackbot.slack')->kick($slackUserId, $slackChannelId, false);
    }

    /**
     * @expectedException Warlof\Seat\Slackbot\Exceptions\SlackGroupException
     */
    public function testKickPrivateChannelException()
    {
        $slackUserId = "U1Z8TCZA3";
        $slackChannelId = "C1Z8J1BFY";

        app('warlof.slackbot.slack')->kick($slackUserId, $slackChannelId, true);
    }

    public function testPublicChannels()
    {
        $apiResponse = app('warlof.slackbot.slack')->channels(false);
        $artifact = $this->getArtifactPath("public_channel.json");

        $this->assertJsonStringEqualsJsonFile($artifact, json_encode($apiResponse[1]));
    }

    public function testPrivateChannels()
    {
        $apiResponse = app('warlof.slackbot.slack')->channels(true);
        $artifact = $this->getArtifactPath("private_channel.json");

        $this->assertJsonStringEqualsJsonFile($artifact, json_encode($apiResponse[0]));
    }

    public function testMembers()
    {
        $apiResponse = app('warlof.slackbot.slack')->members();
        $artifact = $this->getArtifactPath("member.json");

        $this->assertJsonStringEqualsJsonFile($artifact, json_encode($apiResponse[3]));
    }

    public function testRtm()
    {
        $apiResponse = app('warlof.slackbot.slack')->rtmStart();

        $this->assertNotEmpty($apiResponse);
    }

    /**
     * @expectedException Warlof\Seat\Slackbot\Exceptions\SlackApiException
     */
    public function testRtmException()
    {
        $wrongToken = 'xoxp-67298154005-67299441317-67405867777-819c741ccb';
        $testApi = new SlackApi($wrongToken);

        $testApi->rtmStart();
    }

    public function testOwnerKick()
    {
        $testUser = 'U1Z8TCZAT';
        $this->assertNull(app('warlof.slackbot.slack')->kick($testUser, 'C1Z920QKD', false));
    }

    /**
     * @expectedException Warlof\Seat\Slackbot\Exceptions\SlackApiException
     */
    public function testApiException()
    {
        $api = new SlackApi('');
        $api->members();
    }

    private function getArtifactPath($artifactName)
    {
        return dirname(__FILE__) . "/artifacts/" . $artifactName;
    }
}
