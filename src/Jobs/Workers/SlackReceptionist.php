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
use Warlof\Seat\Slackbot\Models\SlackUser;

class SlackReceptionist extends AbstractWorker
{
    public function call()
    {
        // get all Api Key owned by the user
        $keys = ApiKey::where('user_id', $this->user->id)->get();

        // invite user only if both account are subscribed and keys active
        if ($this->user->active == true || Helper::isEnabledKey($keys)) {

            // in other case, invite him to channels and groups
            // get the attached slack user
            if (($slackUser = SlackUser::where('user_id', $this->user->id)->first()) != null) {

                // control that we already know it's slack ID (mean that he creates his account)
                if ($slackUser->slack_id != null) {

                    // fetch user information from caching service
                    $userInfo = Helper::getSlackUserInformation($slackUser->slack_id);

                    $this->processChannelsInvitation($slackUser, $userInfo['channels']);

                    $this->processGroupsInvitation($slackUser, $userInfo['groups']);
                }
            }
        }

        return;
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
        $invitedChannels = [];
        $allowedChannels = Helper::allowedChannels($slackUser, false);
        $missingChannels = array_diff($allowedChannels, $currentChannels);

        // iterate over each channel ID and invite the user
        foreach ($missingChannels as $channelId) {
            if (app('Warlof\Seat\Slackbot\Repositories\SlackApi')->invite($slackUser->slack_id, $channelId, false)) {
                $invitedChannels[] = $channelId;
            }
        }

        $this->logEvent('invite', $invitedChannels);
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
        $invitedGroups = [];
        $allowedGroups = Helper::allowedChannels($slackUser, true);
        $missingGroups = array_diff($allowedGroups, $currentGroups);

        if (!empty($missingGroups)) {

            // iterate over each group ID and invite the user
            foreach ($missingGroups as $groupId) {
                if (app('Warlof\Seat\Slackbot\Repositories\SlackApi')->invite($slackUser->slack_id, $groupId, true)) {
                    $invitedGroups[] = $groupId;
                }
            }

            $this->logEvent('invite', $invitedGroups);
        }
    }
}
