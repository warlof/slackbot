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

        $job_start = microtime(true);

        $token_info = $this->getConnector()->invoke('get', '/auth.test');
        logger()->debug('Slack Receptionist - Checking token', [
            'owner' => $token_info->user_id,
        ]);

        $query = SlackUser::where('slack_id', '<>', $token_info->user_id);

        if ($this->job_payload->owner_id > 0) {
            $query->where('user_id', (int) $this->job_payload->owner_id);
            $this->writeInfoJobLog('Restricting job to a single user : ' . $this->job_payload->owner_id);
        }

        $users = $query->get();

        $channels = $this->fetchSlackConversations();

        logger()->debug('Slack Kicker - channels list', $channels);

        foreach ($channels as $channel) {

            if ($channel->is_general)
                continue;

            $members = $this->fetchSlackConversationMembers($channel->id);

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
                    'user' => $user,
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

                    $this->updateJobStatus([
                        'output' => sprintf('Processing user %s (%s)', $user->user_id, $user->slack_id),
                    ]);

                    try {

                        $this->getConnector()->setBody([
                            'channel' => $channel->id,
                            'user'    => $user->slack_id,
                        ])->invoke( 'post', '/conversations.kick');

                    } catch (RequestFailedException $e) {

                        // catch error related to unknown member
                        if ($e->getError() == 'invalid_membership') {
                            $user->delete();
                            continue;
                        }

                        // if error is not related to unknown member, just forward the initial exception
                        throw $e;
                    }

                    $this->logKickEvent($channel->id, $user->slack_id);
                    sleep(1);
                }
            }

        }

        logger()->debug('Slack kicker - clearing cached data');
        Cache::tags(['conversations', 'members'])->flush();

        $this->writeInfoJobLog('The full kicking process took ' .
            number_format(microtime(true) - $job_start, 2) . 's to complete.');

        $this->updateJobStatus([
            'status' => 'Done',
            'output' => 'The full kicking process took ' .
                number_format(microtime(true) - $job_start, 2) . 's to complete.',
        ]);
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
