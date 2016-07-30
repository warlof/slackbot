<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 19:01
 */

namespace Seat\Slackbot\Jobs;

use Seat\Eveapi\Models\Eve\ApiKey;
use Seat\Slackbot\Exceptions\SlackChannelException;
use Seat\Slackbot\Exceptions\SlackGroupException;
use Seat\Slackbot\Exceptions\SlackMailException;
use Seat\Slackbot\Models\SlackLog;
use Seat\Web\Models\User;
use Seat\Slackbot\Exceptions\SlackTeamInvitationException;
use Seat\Slackbot\Models\SlackUser;

class SlackReceptionist extends AbstractSlack
{

    function call()
    {
        // call the parent call method in order to load the Slack Api Token
        parent::call();

        // get all Api Key owned by the user
        $keys = ApiKey::where('user_id', $this->user->id)->get();

        // invite user only if both account are subscribed and keys active
        if ($this->isEnabledKey($keys) && $this->isActive($keys)) {
            // if the user is not yet invited, invite him to team
            if ($this->isInvited($this->user) == false) {
                $this->processMemberInvitation($this->user);
            // in other case, invite him to channels and groups
            } else {
                // get the attached slack user
                $slackUser = SlackUser::where('user_id', $this->user->id)->first();
                // control that we already know it's slack ID (mean that he creates his account
                if ($slackUser->slack_id != null) {
                    $allowedChannels = $this->allowedChannels($slackUser, false);
                    $allowedGroups = $this->allowedChannels($slackUser, true);

                    $this->processChannelsInvitation($slackUser, $allowedChannels);

                    $slackLog = new SlackLog();
                    $slackLog->event = 'invite';
                    $slackLog->message = 'The user ' . $this->user->name .
                        ' has been invited to following channels : ' . implode(',', $allowedChannels);
                    $slackLog->save();

                    $this->processGroupsInvitation($slackUser, $allowedGroups);

                    $slackLog = new SlackLog();
                    $slackLog->event = 'invite';
                    $slackLog->message = 'The user ' . $this->user->name .
                        ' has been invited to following channels : ' . implode(',', $allowedGroups);
                    $slackLog->save();
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
    function processMemberInvitation(User $user)
    {
        try {
            $this->getSlackApi()->inviteToTeam($user->email);

            // update Slack user relation
            $slackUser = new SlackUser();
            $slackUser->user_id = $user->id;
            $slackUser->invited = true;
            $user->save();
        } catch (SlackMailException $e) {
            $slackLog = new SlackLog();
            $slackLog->event = 'mail';
            $slackLog->message = 'The mail address for user ' . $user->name . ' has not been set (' . $user->email .')';
            $slackLog->save();
        }
    }

    /**
     * Invite an user to each channel
     * 
     * @param SlackUser $slackUser
     * @param array $channels
     * @throws SlackChannelException
     */
    function processChannelsInvitation(SlackUser $slackUser, $channels)
    {
        // iterate over each channel ID and invite the user
        foreach ($channels as $channelId) {
            $this->getSlackApi()->invite($slackUser->slack_id, $channelId, false);
        }
    }

    /**
     * Invite an user to each group
     * 
     * @param SlackUser $slackUser
     * @param array $groups
     * @throws SlackGroupException
     */
    function processGroupsInvitation(SlackUser $slackUser, $groups)
    {
        // iterate over each group ID and invite the user
        foreach ($groups as $groupId) {
            $this->getSlackApi()->invite($slackUser->slack_id, $groupId, true);
        }
    }
}
