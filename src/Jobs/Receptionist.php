<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 09/12/2017
 * Time: 14:53
 */

namespace Warlof\Seat\Slackbot\Jobs;


use Illuminate\Support\Facades\Cache;
use Seat\Eveapi\Jobs\Base;
use Warlof\Seat\Slackbot\Helpers\Helper;
use Warlof\Seat\Slackbot\Http\Controllers\Services\Traits\SlackApiConnector;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Models\SlackLog;
use Warlof\Seat\Slackbot\Models\SlackUser;

class Receptionist extends Base {

    use SlackApiConnector;

    /**
     * @var array
     */
    private $pendingInvitations = [];

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

        $this->writeInfoJobLog('Starting Slack Reception...');

        $jobStart = microtime(true);

        $users = $this->getUsersList();

        foreach ($users as $user) {
            $this->bufferingInvitations($user);
        }

        Cache::tags(['conversations', 'members'])->flush();

        $this->writeInfoJobLog('Pending invitation list has been seeded. Sending invitation...');

        foreach ($this->pendingInvitations as $channelID => $userList) {
            $this->sendInvitationWaves($channelID, $userList);
        }

        $this->writeInfoJobLog('The full invitation process took ' .
            number_format(microtime(true) - $jobStart, 2) . 's to complete.');

        $this->updateJobStatus([
            'status' => 'Done',
            'output' => null,
        ]);

        return;
    }

    private function getUsersList()
    {
        // retrieve information related to the current token
        // so we can remove the user owner from process since we're not able to do things on ourselves
        $tokenInfo = $this->getConnector()->invoke('get', '/auth.test');
        logger()->debug('Slack Receptionist - Checking token', [
            'owner' => $tokenInfo->user_id,
        ]);

        $query = SlackUser::where('slack_id', '<>', $tokenInfo->user_id);

        if ($this->job_payload->owner_id > 0) {
            $query->where('user_id', (int) $this->job_payload->owner_id);
            $this->writeInfoJobLog('Restricting job to a single user : ' . $this->job_payload->owner_id);
            logger()->debug('Slack Receptionist - Restricting job to a single user : ' . $this->job_payload->owner_id);
        }

        return $query->get();
    }

	/**
	 * @param $user
	 *
	 * @throws \Seat\Services\Exceptions\SettingException
	 * @throws \Warlof\Seat\Slackbot\Exceptions\SlackSettingException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
	 * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
	 */
    private function bufferingInvitations($user)
    {
        $grantedChannels = array_merge(
            Helper::allowedChannels($user, true),
            Helper::allowedChannels($user, false));

        logger()->debug('Slack Receptionist - Retrieving granted channels list', [
            'user' => [
                'seat'  => $user->seat_id,
                'slack' => $user->slack_id,
            ],
            'channels' => $grantedChannels,
        ]);

        foreach ($grantedChannels as $channelID) {
            $members = $this->fetchSlackConversationMembers($channelID);

            if (in_array($user->slack_id, $members))
            	continue;

	        // if user is not already member of the channel, put Slack ID in queue
            logger()->debug('Slack reception - buffering invitation', [
                'slack_user_id' => $user->slack_id,
                'channel_id' => $channelID,
            ]);

            if (!array_key_exists($channelID, $this->pendingInvitations))
                $this->pendingInvitations[$channelID] = [];

            $this->pendingInvitations[$channelID][] = $user->slack_id;
        }
    }

    private function sendInvitationWaves(string $channelID, $userList)
    {
        $this->writeInfoJobLog('Starting invitation to channel ' . $channelID);
        logger()->debug('Slack Receptionist - Starting invitation', [
            'channel' => $channelID,
            'users'   => $userList,
        ]);

        // split user list into sub list of maximum 30 user ID
        // in order to send less invitation queries as possible
        foreach (collect($userList)->chunk(30)->toArray() as $userChunk) {
            $this->getConnector()->setBody([
                'channel' => $channelID,
                'users' => implode(',', $userChunk),
            ])->invoke('post', '/conversations.invite');

            $this->logInvitationEvents($channelID, $userChunk);
            sleep(1);
        }
    }

    private function logInvitationEvents(string $channelID, array $userIDs)
    {
        foreach ($userIDs as $user) {
            $slackUser = SlackUser::where('slack_id', $user)->first();
            $slackChannel = SlackChannel::find($channelID);

            SlackLog::create([
                'event' => 'invite',
                'message' => sprintf('The user %s (%s) has been invited to the following channel : %s',
                    $slackUser->name, $slackUser->user->name, $slackChannel->name),
            ]);
        }
    }
}
