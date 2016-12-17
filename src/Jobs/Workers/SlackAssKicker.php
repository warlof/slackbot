<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 19:04
 */

namespace Warlof\Seat\Slackbot\Jobs\Workers;

use Seat\Eveapi\Models\Eve\ApiKey;
use Warlof\Seat\Slackbot\Helpers\Helper;
use Warlof\Seat\Slackbot\Helpers\SlackApi;
use Warlof\Seat\Slackbot\Models\SlackUser;

class SlackAssKicker extends AbstractWorker
{
    public function call()
    {
        // get all Api Key owned by the user
        $keys = ApiKey::where('user_id', $this->user->id)->get();
        // get the Slack Api User
        $slackUser = SlackUser::where('user_id', $this->user->id)
            ->where('invited', true)
            ->whereNotNull('slack_id')
            ->first();

        if ($slackUser != null) {
            // get channels into which current user is already member
            $channels = app(SlackApi::class)->member($slackUser->slack_id, false);
            $groups = app(SlackApi::class)->member($slackUser->slack_id, true);

            // if key are not valid OR account no longer paid
            // kick the user from all channels to which he's member
            if (Helper::isEnabledKey($keys) == false) {

                if (!empty($channels)) {
                    $this->processChannelsKick($slackUser, $channels);
                    $this->logEvent('kick', $channels);
                }

                if (!empty($groups)) {
                    $this->processGroupsKick($slackUser, $groups);
                    $this->logEvent('kick', $groups);
                }

                return;
            }

            // in other way, compute the gap and kick only the user
            // to channel from which he's no longer granted to be in
            $allowedChannels = Helper::allowedChannels($slackUser, false);
            $extraChannels = array_diff($channels, $allowedChannels);

            // remove channels in which user is already in from all granted channels and invite him
            if (!empty($extraChannels)) {
                $this->processChannelsKick($slackUser, $extraChannels);
                $this->logEvent('kick', $extraChannels);
            }

            // remove granted channels from channels in which user is already in and kick him
            $allowedGroups = Helper::allowedChannels($slackUser, true);
            $extraGroups = array_diff($groups, $allowedGroups);
            if (!empty($extraGroups)) {
                $this->processGroupsKick($slackUser, array_diff($groups, $extraGroups));
                $this->logEvent('kick', $extraGroups);
            }
        }

        return;
    }

    /**
     * Kick an user from each channel
     *
     * @param SlackUser $slackUser
     * @param $channels
     * @throws \Warlof\Seat\Slackbot\Exceptions\SlackChannelException
     */
    private function processChannelsKick(SlackUser $slackUser, $channels)
    {
        // iterate channel ID and call kick method from Slack Api
        foreach ($channels as $channelId) {
            app(SlackApi::class)->kick($slackUser->slack_id, $channelId, false);
        }
    }

    /**
     * Kick an user from each group
     *
     * @param SlackUser $slackUser
     * @param $groups
     * @throws \Warlof\Seat\Slackbot\Exceptions\SlackGroupException
     */
    private function processGroupsKick(SlackUser $slackUser, $groups)
    {
        // iterate group ID and call kick method from Slack Api
        foreach ($groups as $groupId) {
            app(SlackApi::class)->kick($slackUser->slack_id, $groupId, true);
        }
    }

}
