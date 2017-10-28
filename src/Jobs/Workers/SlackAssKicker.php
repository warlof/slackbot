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
use Warlof\Seat\Slackbot\Repositories\SlackApi;

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

            // if key are not valid OR account no longer enable
            // kick the user from all channels from which he's member
            if (Helper::isEnabledAccount($this->user) == false || Helper::isEnabledKey($keys) == false) {

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
     */
    private function processChannelsKick(SlackUser $slackUser, array $currentChannels, bool $kickFromAllChannels)
    {
        $this->processKick($slackUser, $currentChannels, $kickFromAllChannels, false);
    }

    /**
     * Kick an user from each group
     *
     * @param SlackUser $slackUser
     * @param $currentGroups
     * @param $kickFromAllGroups
     */
    private function processGroupsKick(SlackUser $slackUser, array $currentGroups, bool $kickFromAllGroups)
    {
        $this->processKick($slackUser, $currentGroups, $kickFromAllGroups, true);
    }

    /**
     * Kick an user
     *
     * @param SlackUser $slackUser
     * @param array $current
     * @param bool $all
     * @param bool $private
     */
    private function processKick(SlackUser $slackUser, array $current, bool $all, bool $private)
    {
        $kicked = [];
        $allowed = Helper::allowedChannels($slackUser, $private);
        $extra = array_diff($current, $allowed);

        if ($all) {
            $extra = $current;
        }

        if (!empty($extra)) {

            // iterate group ID and call kick method from Slack Api
            foreach ($extra as $channelID) {
                if (app(SlackApi::class)->kickFromConversion($slackUser->slack_id, $channelID)) {
                    $kicked[] = $channelID;
                }
            }

            $this->logEvent('kick', $kicked);
        }
    }

}
