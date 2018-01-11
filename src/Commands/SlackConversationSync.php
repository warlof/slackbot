<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 18/12/2017
 * Time: 11:03
 */

namespace Warlof\Seat\Slackbot\Commands;


use Illuminate\Console\Command;
use Seat\Eveapi\Helpers\JobPayloadContainer;
use Seat\Services\Helpers\AnalyticsContainer;
use Seat\Services\Jobs\Analytics;
use Warlof\Seat\Slackbot\Jobs\SyncConversation;

class SlackConversationSync extends Command {

    use JobManager;

    protected $signature = 'slack:conversation:sync';

    protected $description = 'Fire a job which will attempt to pull conversations static information from Slack Team.';

    public function handle(JobPayloadContainer $container)
    {
        $container->api      = 'Slack';
        $container->scope    = 'Conversations';
        $container->owner_id = 0;

        $job_id = $this->addUniqueJob(SyncConversation::class, $container);

        $this->info('Job ' . $job_id . ' dispatched!');

        dispatch((new Analytics((new AnalyticsContainer())
            ->set('type', 'event')
            ->set('ec', 'queues')
            ->set('ea', 'queue_tokens')
            ->set('el', 'console')
            ->set('ev', 1)))
        ->onQueue('medium'));
    }

}