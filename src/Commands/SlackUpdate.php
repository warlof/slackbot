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
use Seat\Slackbot\Jobs\SlackUpdater;
use Seat\Web\Models\User;

class SlackUpdate extends Command
{
    use JobManager;
    
    protected $signature = 'slack:update';

    protected $description = 'Auto invite and kick member based on white list/slack relation';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(JobContainer $job)
    {
        User::where('active', true)->chunk(10, function($users) use ($job) {

            foreach ($users as $user) {
                $job->scope = 'Slack';
                $job->api = 'Scheduler';
                $job->owner_id = $user->id;
                $job->user = $user;

                $jobId = $this->addUniqueJob(
                    SlackUpdater::class, $job
                );

                $this->info('Job ' . $jobId . ' dispatched');
            }
        });
    }
}
