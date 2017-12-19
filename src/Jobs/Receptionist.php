<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 09/12/2017
 * Time: 14:53
 */

namespace Warlof\Seat\Slackbot\Jobs;


use Illuminate\Support\Facades\Cache;
use Warlof\Seat\Slackbot\Helpers\Helper;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Models\SlackLog;
use Warlof\Seat\Slackbot\Models\SlackUser;

class Receptionist extends AbstractSlackJob {

    /**
     * @var array
     */
    private $pending_invitations = [];

    public function handle() {

        if (!$this->trackOrDismiss())
            return;

        $this->updateJobStatus([
            'status' => 'Working',
        ]);

        $this->writeInfoJobLog('Starting Slack Reception...');
        logger()->debug('Slack Receptionist - Starting job...');

        $job_start = microtime(true);

        // retrieve information related to the current token
        // so we can remove the user owner from process since we're not able to do things on ourselves
        $token_info = $this->slack->invoke('get', '/auth.test');
        logger()->debug('Slack Receptionist - Checking token', [
        	'owner' => $token_info->user_id,
        ]);

        $query = SlackUser::where('slack_id', '<>', $token_info->user_id);

        if ($this->job_payload->owner_id > 0) {
            $query->where('user_id', (int) $this->job_payload->owner_id);
            $this->writeInfoJobLog('Restricting job to a single user : ' . $this->job_payload->owner_id);
            logger()->debug('Slack Receptionist - Restricting job to a single user : ' . $this->job_payload->owner_id);
        }

        $users = $query->get();

        foreach ($users as $user) {
            $granted_channels = array_merge(
                Helper::allowedChannels($user, true),
                Helper::allowedChannels($user, false));

            logger()->debug('Slack Receptionist - Retrieving granted channels list', [
            	'user' => [
            		'seat'  => $user->seat_id,
		            'slack' => $user->slack_id,
	            ],
	            'channels' => $granted_channels,
            ]);

            foreach ($granted_channels as $channel_id) {
                $this->fetchingSlackConversationMembers($user->slack_id, $channel_id);
            }
        }

	    logger()->debug('Receptionist - clearing cached data');
	    Cache::tags(['conversations', 'members'])->flush();

        $this->writeInfoJobLog('Pending invitation list has been seeded. Sending invitation...');

        foreach ($this->pending_invitations as $channel_id => $user_list) {
            $this->writeInfoJobLog('Starting invitation to channel ' . $channel_id);
            logger()->debug('Slack Receptionist - Starting invitation', [
            	'channel' => $channel_id,
	            'users'   => $user_list,
            ]);

            // split user list into sub list of maximum 30 user ID
            // in order to send less invitation queries as possible
            foreach (collect($user_list)->chunk(30)->toArray() as $user_chunk) {
                $this->slack->setBody([
                    'channel' => $channel_id,
                    'users' => implode(',', $user_chunk),
                ])->invoke('post', '/conversations.invite');

                $this->logInvitationEvents($channel_id, $user_chunk);
            }
        }

        $this->writeInfoJobLog('The full invitation process took ' .
            number_format(microtime(true) - $job_start, 2) . 's to complete.');

        $this->updateJobStatus([
            'status' => 'Done',
            'output' => null,
        ]);

        return;
    }

    private function fetchingSlackConversationMembers(string $slack_id, string $channel_id, string $cursor = null)
    {
        $this->slack->setQueryString([
            'channel' => $channel_id,
        ]);

        if (!is_null($cursor))
            $this->slack->setQueryString([
                'channel' => $channel_id,
                'cursor' => $cursor,
            ]);

        $response = Cache::tags(['conversations', 'members'])->get(is_null($cursor) ? 'root' : $cursor);

        if (is_null($response)) {
	        $response = $this->slack->invoke( 'get', '/conversations.members' );
	        Cache::tags(['conversations', 'members'])->put(is_null($cursor) ? 'root' : $cursor, $response);
        }

        logger()->debug('Slack reception - channel members', [
        	'channel_id' => $channel_id,
        	'members' => $response->members,
        ]);

        // if user is not already member of the channel, put Slack ID in queue
        if (!in_array($slack_id, $response->members)) {
        	logger()->debug('Slack reception - buffering invitation', [
        		'slack_user_id' => $slack_id,
		        'channel_id' => $channel_id,
	        ]);

            if (!array_key_exists($channel_id, $this->pending_invitations))
                $this->pending_invitations[$channel_id] = [];

            $this->pending_invitations[$channel_id][] = $slack_id;
        }

        if (property_exists($response, 'response_metadata') && $response->response_metadata->next_cursor != '') {
            sleep(1);
            $this->fetchingSlackConversationMembers($slack_id, $channel_id, $response->response_metadata->next_cursor);
        }
    }

    private function logInvitationEvents(string $channel_id, array $user_ids)
    {
        foreach ($user_ids as $user) {
            $slackUser = SlackUser::where('slack_id', $user)->first();
            $slackChannel = SlackChannel::find($channel_id);

            SlackLog::create([
                'event' => 'invite',
                'message' => sprintf('The user %s (%s) has been invited to the following channel : %s',
                    $slackUser->name, $slackUser->user->name, $slackChannel->name),
            ]);
        }
    }
}