<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 19:04
 */

namespace Warlof\Seat\Slackbot\Jobs\Workers;

use Seat\Eveapi\Models\Eve\ApiKey;
use Warlof\Seat\Slackbot\Helpers\Helper;
use Warlof\Seat\Slackbot\Models\SlackUser;

class SlackAssKicker extends AbstractWorker
{
    public function call()
    {
        // get all Api Key owned by the user
        $keys = ApiKey::where('user_id', $this->user->id)->get();
        // get the Slack Api User
        $slackUser = SlackUser::where('user_id', $this->user->id)
            ->whereNotNull('slack_id')
            ->first();

        if ($slackUser != null) {
            // get channels into which current user is already member

            $userInfo = Helper::getSlackUserInformation($slackUser->slack_id);

            // if key are not valid OR account no longer paid
            // kick the user from all channels to which he's member
            if (Helper::isEnabledKey($keys) == false) {

                $this->processChannelsKick($slackUser, $userInfo['channels'], true);

                $this->processGroupsKick($slackUser, $userInfo['groups'], true);

                return;
            }

            // remove channels in which user is already in from all granted channels and invite him
            $this->processChannelsKick($slackUser, $userInfo['channels'], false);

            // remove granted channels from channels in which user is already in and kick him
            $this->processGroupsKick($slackUser, $userInfo['groups'], false);
        }

        return;
    }

    /**
     * Kick an user from each channel
     *
     * @param SlackUser $slackUser
     * @param $currentChannels
     * @param $kickFromAllChannels
     * @throws \Warlof\Seat\Slackbot\Exceptions\SlackChannelException
     */
    private function processChannelsKick(SlackUser $slackUser, array $currentChannels, bool $kickFromAllChannels)
    {
        $kickedChannels = [];
        $allowedChannels = Helper::allowedChannels($slackUser, false);
        $extraChannels = array_diff($currentChannels, $allowedChannels);

        if ($kickFromAllChannels) {
            $extraChannels = $currentChannels;
        }

        if (!empty($extraChannels)) {

            // iterate channel ID and call kick method from Slack Api
            foreach ($extraChannels as $channelId) {
                if (app('Warlof\Seat\Slackbot\Repositories\SlackApi')->kick($slackUser->slack_id, $channelId, false)) {
                    $kickedChannels[] = $channelId;
                }
            }

            $this->logEvent('kick', $kickedChannels);
        }
    }

    /**
     * Kick an user from each group
     *
     * @param SlackUser $slackUser
     * @param $currentGroups
     * @param $kickFromAllGroups
     * @throws \Warlof\Seat\Slackbot\Exceptions\SlackGroupException
     */
    private function processGroupsKick(SlackUser $slackUser, array $currentGroups, bool $kickFromAllGroups)
    {
        $kickedGroups = [];
        $allowedGroups = Helper::allowedChannels($slackUser, true);
        $extraGroups = array_diff($currentGroups, $allowedGroups);

        if ($kickFromAllGroups) {
            $extraGroups = $currentGroups;
        }

        if (!empty($extraGroups)) {

            // iterate group ID and call kick method from Slack Api
            foreach ($extraGroups as $groupId) {
                if (app('Warlof\Seat\Slackbot\Repositories\SlackApi')->kick($slackUser->slack_id, $groupId, true)) {
                    $kickedGroups[] = $groupId;
                }
            }

            $this->logEvent('kick', $kickedGroups);
        }
    }

}
