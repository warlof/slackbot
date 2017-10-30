<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 19:01
 */

namespace Warlof\Seat\Slackbot\Jobs\Workers;

use Seat\Eveapi\Models\Eve\ApiKey;
use Warlof\Seat\Slackbot\Exceptions\SlackConversationException;
use Warlof\Seat\Slackbot\Helpers\Helper;
use Warlof\Seat\Slackbot\Models\SlackUser;
use Warlof\Seat\Slackbot\Repositories\SlackApi;

class SlackReceptionist extends AbstractWorker
{
    public function call()
    {
        // get all Api Key owned by the user
        $keys = ApiKey::where('user_id', $this->user->id)->get();

        // invite user only if account is enabled and both account are subscribed and keys active
        if (Helper::isEnabledAccount($this->user) == true && Helper::isEnabledKey($keys)) {

            // in other case, invite him to channels and groups
            // get the attached slack user
            if (($slackUser = SlackUser::where('user_id', $this->user->id)->first()) != null) {

                // control that we already know it's slack ID (mean that he creates his account)
                if ($slackUser->slack_id != null) {

                    // fetch user information from caching service
                    $userInfo = Helper::getSlackUserInformation($slackUser->slack_id);

                    // quick backward compatibility hotfix, must be clean asap
                    $groups = [];
                    $channels = [];

                    foreach ($userInfo['conversations'] as $conversation) {
                        if (strpos($conversation, 'C') === 0) {
                            $channels[] = $conversation;
                            continue;
                        }

                        $groups[] = $conversation;
                    }

                    $this->processChannelsInvitation($slackUser, $channels);

                    $this->processGroupsInvitation($slackUser, $groups);
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
     * @throws SlackConversationException
     */
    private function processChannelsInvitation(SlackUser $slackUser, array $currentChannels)
    {
        $invitedChannels = [];
        $allowedChannels = Helper::allowedChannels($slackUser, false);
        $missingChannels = array_diff($allowedChannels, $currentChannels);

        // iterate over each channel ID and invite the user
        foreach ($missingChannels as $channelId) {
            if (app(SlackApi::class)->inviteIntoConversation($slackUser->slack_id, $channelId)) {
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
     */
    private function processGroupsInvitation(SlackUser $slackUser, array $currentGroups)
    {
        $invitedGroups = [];
        $allowedGroups = Helper::allowedChannels($slackUser, true);
        $missingGroups = array_diff($allowedGroups, $currentGroups);

        if (!empty($missingGroups)) {

            // iterate over each group ID and invite the user
            foreach ($missingGroups as $groupId) {
                if (app(SlackApi::class)->inviteIntoConversation($slackUser->slack_id, $groupId)) {
                    $invitedGroups[] = $groupId;
                }
            }

            $this->logEvent('invite', $invitedGroups);
        }
    }
}
