<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 09/12/2017
 * Time: 18:49
 */

namespace Warlof\Seat\Slackbot\Jobs;


use Illuminate\Support\Facades\Cache;
use Seat\Eveapi\Jobs\Base;
use Warlof\Seat\Slackbot\Helpers\Helper;
use Warlof\Seat\Slackbot\Http\Controllers\Services\Traits\SlackApiConnector;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Models\SlackLog;
use Warlof\Seat\Slackbot\Models\SlackUser;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException;

class AssKicker extends Base {

    use SlackApiConnector;

    /**
     * @return mixed|void
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Exceptions\SlackSettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    public function handle() {

        if (!$this->trackOrDismiss())
            return;

        $this->updateJobStatus([
            'status' => 'Working',
        ]);

        $this->writeInfoJobLog('Starting Slack Ass Kicker...');

        $jobStart = microtime(true);

        $users = $this->getUsersList();

        $channels = $this->fetchSlackConversations();

        foreach ($channels as $channel) {
            $this->proceedChannelAnalysis($channel, $users);
        }

        logger()->debug('Slack kicker - clearing cached data');
        Cache::tags(['conversations', 'members'])->flush();

        $this->writeInfoJobLog('The full kicking process took ' .
            number_format(microtime(true) - $jobStart, 2) . 's to complete.');

        $this->updateJobStatus([
            'status' => 'Done',
            'output' => null,
        ]);
    }

    private function getUsersList()
    {
        $tokenInfo = $this->getConnector()->invoke('get', '/auth.test');
        logger()->debug('Slack Receptionist - Checking token', [
            'owner' => $tokenInfo->user_id,
        ]);

        $query = SlackUser::where('slack_id', '<>', $tokenInfo->user_id);

        if ($this->job_payload->owner_id > 0) {
            $query->where('user_id', (int) $this->job_payload->owner_id);
            $this->writeInfoJobLog('Restricting job to a single user : ' . $this->job_payload->owner_id);
        }

        return $query->get();
    }

    /**
     * @param $channel
     * @param $users
     *
     * @throws RequestFailedException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Exceptions\SlackSettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    private function proceedChannelAnalysis($channel, $users)
    {
        if ($channel->is_general)
            return;

        $members = $this->fetchSlackConversationMembers($channel->id);

        logger()->debug('Slack Kicker - Channel members', [
            'channel' => $channel->id,
            'members' => $members
        ]);

        foreach ($users as $user) {

            if (!in_array($user->slack_id, $members))
                continue;

            $this->sendKickWave($channel, $user);
        }
    }

    /**
     * @param $channel
     * @param $user
     *
     * @throws RequestFailedException
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Exceptions\SlackSettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    private function sendKickWave($channel, $user)
    {
        $grantedChannels = array_merge(
            Helper::allowedChannels($user, true),
            Helper::allowedChannels($user, false));

        logger()->debug('Slack Kicker - Granted channels', [
            'user' => $user,
            'channels' => $grantedChannels,
        ]);

        if (in_array($channel->id, $grantedChannels))
            return;

        try {

            $this->getConnector()->setBody([
                'channel' => $channel->id,
                'user'    => $user->slack_id,
            ])->invoke( 'post', '/conversations.kick' );

        } catch (RequestFailedException $e) {

            // catch error related to unknown member
            if ($e->getError() == 'invalid_memberships') {
                $user->delete();
                sleep(1);
                return;
            }

            // if error is not related to unknown member, just forward the initial exception
            throw $e;
        }

        $this->logKickEvent($channel->id, $user->slack_id);
        sleep(1);
    }

    private function logKickEvent(string $channelId, string $userId)
    {
        $slackUser = SlackUser::where('slack_id', $userId)->first();
        $slackChannel = SlackChannel::find($channelId);

        SlackLog::create([
            'event' => 'kick',
            'message' => sprintf('The user %s (%s) has been kicked from the following channel : %s',
                $slackUser->name, $slackUser->user->name, $slackChannel->name),
        ]);
    }

}
