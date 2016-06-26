<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 25/06/2016
 * Time: 20:09
 */

namespace Seat\Slackbot\Jobs;

use App\Jobs\Job;
use Exception;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seat\Eveapi\Helpers\JobContainer;
use Seat\Eveapi\Traits\JobManager;
use Seat\Eveapi\Traits\JobTracker;
use Seat\Slackbot\Services\SlackRtmDaemon;

class SlackService extends Job implements SelfHandling, ShouldQueue
{

    use InteractsWithQueue, SerializesModels, JobTracker, JobManager;

    protected $jobPayload;

    public function __construct(JobContainer $jobPayload)
    {
        $this->jobPayload = $jobPayload;
    }

    public function handle(JobContainer $jobContainer)
    {
        $jobTracker = $this->trackOrDismiss();

        if (!$jobTracker)
            return;

        $jobTracker->status = 'Working';
        $jobTracker->output = 'Slack RTM Service is started';
        $jobTracker->save();

        try {
            (new SlackRtmDaemon())->call();
            $this->decrementErrorCounters();
        } catch (Exception $e) {
            $this->reportJobError($jobTracker, $e);
            return;
        }

        $jobTracker->status = 'Done';
        $jobTracker->output = 'Slack RTM Service has been shutdown';
        $jobTracker->save();
    }

}