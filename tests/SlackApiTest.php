<?php

/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 08/08/2016
 * Time: 17:07
 */

namespace Seat\Slackbot\Tests;

use PHPUnit\Framework\TestCase;
use Seat\Slackbot\Exceptions\SlackChannelException;
use Seat\Slackbot\Exceptions\SlackGroupException;
use Seat\Slackbot\Exceptions\SlackMailException;
use Seat\Slackbot\Exceptions\SlackTeamInvitationException;
use Seat\Slackbot\Helpers\SlackApi;

class SlackApiTest extends TestCase
{
    /**
     * @var SlackApi
     */
    private $slackApi;

    public function setUp()
    {
        parent::setUp();

        $token = "xoxp-67298154005-67299441367-67288681844-0828b5aad7";

        $this->slackApi = new SlackApi($token);
    }

    public function testInviteMailException()
    {
        $mail = "example@domain.local";

        $this->expectException(SlackMailException::class);
        $this->slackApi->inviteToTeam($mail);
    }

    public function testInviteTeamException()
    {
        $mail = "e.elfaus@gmail.com";

        $this->expectException(SlackTeamInvitationException::class);
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
        $slackChannelsId = ["G1Z9267L1", "G1Z9CBCP8"];
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
        $slackChannelId = "G1Z9267L1";
        $apiResponse = $this->slackApi->info($slackChannelId, true);
        $artifact = $this->getArtifactPath("private_info.json");

        $this->assertJsonStringEqualsJsonFile($artifact, json_encode($apiResponse));
    }

    public function testKickPublicChannelException()
    {
        $slackUserId = "U1Z8TCZAT";
        $slackChannelId = "C1Z920QKC";

        $this->expectException(SlackChannelException::class);
        $this->slackApi->kick($slackUserId, $slackChannelId, false);
    }

    public function testKickPrivateChannelException()
    {
        $slackUserId = "U1Z8TCZAT";
        $slackChannelId = "C1Z8J1BFY";

        $this->expectException(SlackGroupException::class);
        $this->slackApi->kick($slackUserId, $slackChannelId, true);
    }

    public function testPublicChannels()
    {
        $apiResponse = $this->slackApi->channels(false);
        $artifact = $this->getArtifactPath("public_channel.json");

        $this->assertJsonStringEqualsJsonFile($artifact, json_encode($apiResponse[0]));
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

    private function getArtifactPath($artifactName)
    {
        return dirname(__FILE__) . "/artifacts/" . $artifactName;
    }
}
