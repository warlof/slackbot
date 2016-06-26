<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 18:51
 */

namespace Seat\Slackbot\Commands;


use Illuminate\Console\Command;
use Seat\Eveapi\Helpers\JobContainer;
use Seat\Eveapi\Traits\JobManager;
use Seat\Slackbot\Jobs\SlackService;

class SlackDaemon extends Command
{
    use JobManager;

    protected $signature = 'slack:daemon:run';

    protected $description = 'Slack service which handle Slack event. Mandatory in order to keep slack user up to date.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(JobContainer $job)
    {
        $job->scope = 'Slack';
        $job->api = 'Scheduler';
        $job->owner_id = 0;

        $jobId = $this->addUniqueJob(
            SlackService::class, $job
        );

        $this->info('Job ' . $jobId . ' dispatched');
    }
}
