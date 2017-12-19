<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 09/12/2017
 * Time: 18:49
 */

namespace Warlof\Seat\Slackbot\Jobs;


use Illuminate\Support\Facades\Cache;
use Warlof\Seat\Slackbot\Helpers\Helper;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Models\SlackLog;
use Warlof\Seat\Slackbot\Models\SlackUser;

class AssKicker extends AbstractSlackJob {

    public function handle() {

        if (!$this->trackOrDismiss())
            return;

        $this->updateJobStatus([
            'status' => 'Working',
        ]);

        $this->writeInfoJobLog('Starting Slack Ass Kicker...');

        $job_start = microtime(true);

        $token_info = $this->slack->invoke('get', '/auth.test');
	    logger()->debug('Slack Receptionist - Checking token', [
		    'owner' => $token_info->user_id,
	    ]);

        $query = SlackUser::where('slack_id', '<>', $token_info->user_id);

        if ($this->job_payload->owner_id > 0) {
            $query->where('user_id', (int) $this->job_payload->owner_id);
            $this->writeInfoJobLog('Restricting job to a single user : ' . $this->job_payload->owner_id);
        }

        $users = $query->get();

        $channels = $this->fetchingSlackConversations();

        logger()->debug('Slack Kicker - channels list', $channels);

        foreach ($channels as $channel) {

            if ($channel->is_general)
                continue;

            $members = $this->fetchingSlackConversationMembers($channel->id);

            logger()->debug('Slack Kicker - Channel members', [
            	'channel' => $channel->id,
	            'members' => $members
            ]);

            foreach ($users as $user) {

            	logger()->debug('Slack Kicker - Checking user', [
            		'user'    => $user,
            		'channel' => $channel->id,
            		'members' => $members,
	            ]);

                if (!in_array($user->slack_id, $members))
                    continue;

                $granted_channels = array_merge(
                	Helper::allowedChannels($user, true),
	                Helper::allowedChannels($user, false));

                logger()->debug('Slack Kicker - Granted channels', [
                	'user' => [
                		'seat'  => $user->seat_id,
                		'slack' => $user->slack_id,
	                ],
	                'channels' => $granted_channels,
                ]);

                if (!in_array($channel->id, $granted_channels)) {
                	logger()->debug('Slack Kicker - Kicking user', [
                		'user' => [
                			'seat' => $user->user_id,
			                'slack' => $user->slack_id
		                ],
		                'channel' => $channel->id
	                ]);

                    $this->slack->setBody([
                        'channel' => $channel->id,
                        'user' => $user->slack_id,
                    ])->invoke('post', '/conversations.kick');

                    $this->logKickEvent($channel->id, $user->slack_id);
                }
            }

        }

	    logger()->debug('Slack kicker - clearing cached data');
	    Cache::tags(['conversations', 'members'])->flush();

        $this->writeInfoJobLog('The full kicking process took ' .
            number_format(microtime(true) - $job_start, 2) . 's to complete.');

        $this->updateJobStatus([
            'status' => 'Done',
            'output' => null,
        ]);
    }

    private function fetchingSlackConversations(string $cursor = null) : array
    {
        $this->slack->setQueryString([
            'types' => implode(',', ['public_channel', 'private_channel']),
            'exclude_archived' => true,
        ]);

        if (!is_null($cursor))
            $this->slack->setQueryString([
                'cursor' => $cursor,
                'types' => implode(',', ['public_channel', 'private_channel']),
                'exclude_archived' => true,
            ]);

        $response = Cache::tags(['conversations'])->get(is_null($cursor) ? 'root' : $cursor);

        if (is_null($response)) {
	        $response = $this->slack->invoke('get', '/conversations.list');
	        Cache::tags(['conversations'])->put(is_null($cursor) ? 'root' : $cursor, $response);
        }

        $channels = $response->channels;

        if (property_exists($response, 'response_metadata') && $response->response_metadata->next_cursor != '') {
            sleep(1);
            $channels = array_merge(
            	$channels,
	            $this->fetchingSlackConversations( $response->response_metadata->next_cursor)
            );
        }

        return $channels;
    }

    private function fetchingSlackConversationMembers(string $channel_id, string $cursor = null) : array
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

        $members = $response->members;
	    logger()->debug('Slack kicker - channel members', [
		    'channel_id' => $channel_id,
		    'members' => $response->members,
	    ]);

        if (property_exists($response, 'response_metadata') && $response->response_metadata->next_cursor != '') {
            sleep(1);
            $members = array_merge(
                $members,
                $this->fetchingSlackConversationMembers($channel_id, $response->response_metadata->next_cursor));
        }

        return $members;
    }

    private function logKickEvent(string $channel_id, string $user_id)
    {
        $slackUser = SlackUser::where('slack_id', $user_id)->first();
        $slackChannel = SlackChannel::find($channel_id);

        SlackLog::create([
            'event' => 'kick',
            'message' => sprintf('The user %s (%s) has been kicked from the following channel : %s',
                $slackUser->name, $slackUser->user->name, $slackChannel->name),
        ]);
    }

}
