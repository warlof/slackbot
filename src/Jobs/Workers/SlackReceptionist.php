<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 19:01
 */

namespace Warlof\Seat\Slackbot\Jobs\Workers;

use Illuminate\Support\Facades\Redis;
use Seat\Web\Models\User;
use Seat\Eveapi\Models\Eve\ApiKey;
use Warlof\Seat\Slackbot\Exceptions\SlackChannelException;
use Warlof\Seat\Slackbot\Exceptions\SlackGroupException;
use Warlof\Seat\Slackbot\Exceptions\SlackMailException;
use Warlof\Seat\Slackbot\Exceptions\SlackTeamInvitationException;
use Warlof\Seat\Slackbot\Helpers\Helper;
use Warlof\Seat\Slackbot\Models\SlackUser;

class SlackReceptionist extends AbstractWorker
{
    public function call()
    {
        // get all Api Key owned by the user
        $keys = ApiKey::where('user_id', $this->user->id)->get();

        // invite user only if both account are subscribed and keys active
        if (Helper::isEnabledKey($keys)) {

            // in other case, invite him to channels and groups
            // get the attached slack user
            $slackUser = SlackUser::where('user_id', $this->user->id)->first();

            // control that we already know it's slack ID (mean that he creates his account)
            if ($slackUser->slack_id != null) {

                // fetch user information from caching service
                $userInfo = Helper::getSlackUserInformation($slackUser->slack_id);

                $this->processChannelsInvitation($slackUser, $userInfo['channels']);

                $this->processGroupsInvitation($slackUser, $userInfo['groups']);
            }
        }

        return;
    }

    /**
     * Invite the user to a slack team
     * 
     * @param User $user
     * @throws SlackMailException
     * @throws SlackTeamInvitationException
     * @deprecated Since not an official endpoint and event API is not usable with test token
     */
    private function processMemberInvitation(User $user)
    {
        try {
            app('warlof.slackbot.slack')->inviteToTeam($user->email);

            // update Slack user relation
            SlackUser::create([
                'user_id' => $user->id,
                'invited' => true
            ]);
        } catch (SlackMailException $e) {
            $this->logEvent('mail');
        } catch (SlackTeamInvitationException $e) {
            $this->logEvent('sync');
        }
    }

    /**
     * Invite an user to each channel
     * 
     * @param SlackUser $slackUser
     * @param array $currentChannels
     * @throws SlackChannelException
     */
    private function processChannelsInvitation(SlackUser $slackUser, array $currentChannels)
    {
        $allowedChannels = Helper::allowedChannels($slackUser, true);
        $missingChannels = array_diff($allowedChannels, $currentChannels);

        // iterate over each channel ID and invite the user
        foreach ($missingChannels as $channelId) {
            app('warlof.slackbot.slack')->invite($slackUser->slack_id, $channelId, false);
        }

        $this->logEvent('invite', $missingChannels);
    }

    /**
     * Invite an user to each group
     * 
     * @param SlackUser $slackUser
     * @param array $currentGroups
     * @throws SlackGroupException
     */
    private function processGroupsInvitation(SlackUser $slackUser, array $currentGroups)
    {
        $allowedGroups = Helper::allowedChannels($slackUser, true);
        $missingGroups = array_diff($allowedGroups, $currentGroups);

        if (!empty($missingGroups)) {

            // iterate over each group ID and invite the user
            foreach ($missingGroups as $groupId) {
                app('warlof.slackbot.slack')->invite($slackUser->slack_id, $groupId, true);
            }

            $this->logEvent('invite', $missingGroups);
        }
    }
}
