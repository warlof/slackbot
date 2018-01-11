<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 18/12/2017
 * Time: 11:07
 */

namespace Warlof\Seat\Slackbot\Jobs;


use Seat\Eveapi\Jobs\Base;
use Warlof\Seat\Slackbot\Http\Controllers\Services\Traits\SlackApiConnector;
use Warlof\Seat\Slackbot\Models\SlackChannel;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException;

class SyncConversation extends Base {

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
    public function handle()
    {
        if (!$this->trackOrDismiss())
            return;

        try {

            $this->updateJobStatus([
                'status' => 'Working',
                'output' => 'Starting sync...',
            ]);

            $this->writeInfoJobLog('Starting Slack Sync Conversation...');

            $job_start = microtime(true);

            $conversations        = $this->fetchSlackConversations();
            $conversations_buffer = [];

            foreach ($conversations as $conversation) {

                $conversations_buffer[] = $conversation->id;
                SlackChannel::updateOrCreate(
                    [
                        'id' => $conversation->id,
                    ],
                    [
                        'name'       => $conversation->name,
                        'is_group'   => $conversation->is_group,
                        'is_general' => $conversation->is_general,
                    ]);

            }

            SlackChannel::whereNotIn('id', $conversations_buffer)->delete();

            $this->writeInfoJobLog('The full syncing process took ' .
                                    number_format(microtime(true) - $job_start, 2) . 's to complete.');

            $this->markAsDone();

        } catch (RequestFailedException $e) {

            $this->writeErrorJobLog(
                sprintf('A %s occurred. The error was: %s',
                    'RequestFailedException',
                    $e->getException()->getMessage()));
            $this->reportJobError($e->getException());

        }
    }

}
