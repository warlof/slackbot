<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 25/06/2016
 * Time: 20:30
 */

namespace Seat\Slackbot\Jobs;


use App\Jobs\Job;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seat\Eveapi\Helpers\JobContainer;
use Seat\Eveapi\Traits\JobManager;
use Seat\Eveapi\Traits\JobTracker;

class SlackUpdater extends Job implements SelfHandling, ShouldQueue
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

        if (!$jobTracker) {
            return;
        }

        try {
            $jobTracker->status = 'Working';
            $jobTracker->output = 'Started Slack Update';
            $jobTracker->save();

            (new SlackReceptionist())->setUser($this->jobPayload->user)->call();
            (new SlackAssKicker())->setUser($this->jobPayload->user)->call();

            $jobContainer->api = 'Slack';
            $jobContainer->scope = 'Update';
            $jobContainer->owner_id = $this->jobPayload->owner_id;

            $jobTracker->status = 'Done';
            $jobTracker->output = null;
            $jobTracker->save();

        } catch (\Exception $e) {
            $this->reportJobError($jobTracker, $e);
        }
    }
}