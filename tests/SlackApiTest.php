<?php

/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 08/08/2016
 * Time: 17:07
 */

namespace Seat\Slackbot\Tests;

use Seat\Slackbot\Helpers\SlackApi;

class SlackApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SlackApi
     */
    private $slackApi;

    public function setUp()
    {
        parent::setUp();

        $token = getenv('slack_token');

        $this->slackApi = new SlackApi($token);
    }

    /**
     * @expectedException Seat\Slackbot\Exceptions\SlackMailException
     */
    public function testInviteMailException()
    {
        $mail = "example@domain.local";

        $this->slackApi->inviteToTeam($mail);
    }

    /**
     * @expectedException Seat\Slackbot\Exceptions\SlackTeamInvitationException
     */
    public function testInviteTeamException()
    {
        $mail = "e.elfaus@gmail.com";

        $this->slackApi->inviteToTeam($mail);
    }

    public function testMemberPublicChannel()
    {
        $slackUserId = "U1Z8TCZAT";
        $slackChannelsId = ["C1Z8J1BFY", "C1Z920QKC"];
        $apiResponse = $this->slackApi->member($slackUserId, false);

        $this->assertEquals($apiResponse, $slackChannelsId);
    }

    public function testMemberPrivateChannel()
    {
        $slackUserId = "U1Z8TCZAT";
        $slackChannelsId = ["G1Z9267L1", "G1Z9CBCP8", "G1ZUXJZSL"];
        $apiResponse = $this->slackApi->member($slackUserId, true);

        $this->assertEquals($apiResponse, $slackChannelsId);
    }

    public function testInfoPublicChannel()
    {
        $slackChannelId = "C1Z8J1BFY";
        $apiResponse = $this->slackApi->info($slackChannelId, false);
        $artifact = $this->getArtifactPath("public_info.json");

        $this->assertJsonStringEqualsJsonFile($artifact, json_encode($apiResponse));
    }

    public function testInfoPrivateChannel()
    {
        $slackChannelId = "G1ZUXJZSL";
        $apiResponse = $this->slackApi->info($slackChannelId, true);
        $artifact = $this->getArtifactPath("private_info.json");

        $this->assertJsonStringEqualsJsonFile($artifact, json_encode($apiResponse));
    }

    /**
     * @expectedException Seat\Slackbot\Exceptions\SlackChannelException
     */
    public function testInfoChannelException()
    {
        $slackChannelId = "C2Z4D897";

        $this->slackApi->info($slackChannelId, false);
    }

    /**
     * @expectedException Seat\Slackbot\Exceptions\SlackGroupException
     */
    public function testInfoGroupException()
    {
        $slackChannelId = "C2Z4D897";

        $this->slackApi->info($slackChannelId, true);
    }

    public function testInvitePublicChannel()
    {
        $slackUserId = "U1Z9LT9NK";
        $slackChannelId = "C1Z920QKC";

        $this->assertNull($this->slackApi->invite($slackUserId, $slackChannelId, false));
    }

    public function testInvitePrivateChannel()
    {
        $slackUserId = "U1Z9LT9NK";
        $slackChannelId = "G1Z9267L1";

        $this->assertNull($this->slackApi->invite($slackUserId, $slackChannelId, true));
    }

    /**
     * @expectedException Seat\Slackbot\Exceptions\SlackChannelException
     */
    public function testInvitePublicChannelException()
    {
        $slackUserId = "U1Z9LT9NK";
        $slackChannelId = "C1Z920QKD";

        $this->slackApi->invite($slackUserId, $slackChannelId, false);
    }

    /**
     * @expectedException Seat\Slackbot\Exceptions\SlackGroupException
     */
    public function testInvitePrivateChannelException()
    {
        $slackUserId = "U1Z9LT9NK";
        $slackChannelId = "G1Z9267L2";

        $this->slackApi->invite($slackUserId, $slackChannelId, true);
    }

    public function testKickPublicChannel()
    {
        $slackUserId = "U1Z9LT9NK";
        $slackChannelId = "C1Z920QKC";

        $this->assertNull($this->slackApi->kick($slackUserId, $slackChannelId, false));
    }

    public function testKickPrivateChannel()
    {
        $slackUserId = "U1Z9LT9NK";
        $slackChannelId = "G1Z9267L1";

        $this->assertNull($this->slackApi->kick($slackUserId, $slackChannelId, true));
    }

    /**
     * @expectedException Seat\Slackbot\Exceptions\SlackChannelException
     */
    public function testKickPublicChannelException()
    {
        $slackUserId = "U1Z8TCZA3";
        $slackChannelId = "G1Z920QKC";

        $this->slackApi->kick($slackUserId, $slackChannelId, false);
    }

    /**
     * @expectedException Seat\Slackbot\Exceptions\SlackGroupException
     */
    public function testKickPrivateChannelException()
    {
        $slackUserId = "U1Z8TCZA3";
        $slackChannelId = "C1Z8J1BFY";

        $this->slackApi->kick($slackUserId, $slackChannelId, true);
    }

    public function testPublicChannels()
    {
        $apiResponse = $this->slackApi->channels(false);
        $artifact = $this->getArtifactPath("public_channel.json");

        $this->assertJsonStringEqualsJsonFile($artifact, json_encode($apiResponse[1]));
    }

    public function testPrivateChannels()
    {
        $apiResponse = $this->slackApi->channels(true);
        $artifact = $this->getArtifactPath("private_channel.json");

        $this->assertJsonStringEqualsJsonFile($artifact, json_encode($apiResponse[0]));
    }

    public function testMembers()
    {
        $apiResponse = $this->slackApi->members();
        $artifact = $this->getArtifactPath("member.json");

        $this->assertJsonStringEqualsJsonFile($artifact, json_encode($apiResponse[3]));
    }

    public function testRtm()
    {
        $apiResponse = $this->slackApi->rtmStart();

        $this->assertNotEmpty($apiResponse);
    }

    /**
     * @expectedException Seat\Slackbot\Exceptions\SlackApiException
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
        $this->assertNull($this->slackApi->kick($testUser, 'C1Z920QKD', false));
    }

    private function getArtifactPath($artifactName)
    {
        return dirname(__FILE__) . "/artifacts/" . $artifactName;
    }
}
