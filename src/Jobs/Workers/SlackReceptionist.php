<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 19:01
 */

namespace Warlof\Seat\Slackbot\Jobs\Workers;

use Seat\Web\Models\User;
use Seat\Eveapi\Models\Eve\ApiKey;
use Warlof\Seat\Slackbot\Exceptions\SlackChannelException;
use Warlof\Seat\Slackbot\Exceptions\SlackGroupException;
use Warlof\Seat\Slackbot\Exceptions\SlackMailException;
use Warlof\Seat\Slackbot\Exceptions\SlackTeamInvitationException;
use Warlof\Seat\Slackbot\Helpers\Helper;
use Warlof\Seat\Slackbot\Helpers\SlackApi;
use Warlof\Seat\Slackbot\Models\SlackUser;

class SlackReceptionist extends AbstractWorker
{
    public function call()
    {
        // get all Api Key owned by the user
        $keys = ApiKey::where('user_id', $this->user->id)->get();

        // invite user only if both account are subscribed and keys active
        if (Helper::isEnabledKey($keys)) {
            // if the user is not yet invited, invite him to team
            if ($this->isInvited($this->user) == false) {
                $this->processMemberInvitation($this->user);
                return;
            }

            // in other case, invite him to channels and groups
            // get the attached slack user
            $slackUser = SlackUser::where('user_id', $this->user->id)->first();
            // control that we already know it's slack ID (mean that he creates his account)
            if ($slackUser->slack_id != null) {
                $allowedChannels = Helper::allowedChannels($slackUser, false);
                $memberOfChannels = app(SlackApi::class)->member($slackUser->slack_id, false);
                $missingChannels = array_diff($allowedChannels, $memberOfChannels);

                if (!empty($missingChannels)) {
                    $this->processChannelsInvitation($slackUser, $missingChannels);
                    $this->logEvent('invite', $missingChannels);
                }

                $allowedGroups = Helper::allowedChannels($slackUser, true);
                $memberOfGroups = app(SlackApi::class)->member($slackUser->slack_id, true);
                $missingGroups = array_diff($allowedGroups, $memberOfGroups);

                if (!empty($missingGroups)) {
                    $this->processGroupsInvitation($slackUser, $missingGroups);
                    $this->logEvent('invite', $missingGroups);
                }
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
     */
    private function processMemberInvitation(User $user)
    {
        try {
            app(SlackApi::class)->inviteToTeam($user->email);

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
     * @param array $channels
     * @throws SlackChannelException
     */
    private function processChannelsInvitation(SlackUser $slackUser, array $channels)
    {
        // iterate over each channel ID and invite the user
        foreach ($channels as $channelId) {
            app(SlackApi::class)->invite($slackUser->slack_id, $channelId, false);
        }
    }

    /**
     * Invite an user to each group
     * 
     * @param SlackUser $slackUser
     * @param array $groups
     * @throws SlackGroupException
     */
    private function processGroupsInvitation(SlackUser $slackUser, array $groups)
    {
        // iterate over each group ID and invite the user
        foreach ($groups as $groupId) {
            app(SlackApi::class)->invite($slackUser->slack_id, $groupId, true);
        }
    }
}
