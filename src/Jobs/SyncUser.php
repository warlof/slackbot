<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 09/12/2017
 * Time: 11:33
 */

namespace Warlof\Seat\Slackbot\Jobs;


use Illuminate\Support\Facades\DB;
use Warlof\Seat\Slackbot\Models\SlackLog;
use Warlof\Seat\Slackbot\Models\SlackUser;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\RequestFailedException;

class SyncUser extends AbstractSlackJob {

    public function handle() {

        if (!$this->trackOrDismiss())
            return;

        $this->updateJobStatus([
            'status' => 'Working',
        ]);

        $this->writeInfoJobLog('Starting Slack Sync User...');

        $job_start = microtime(true);

        // retrieve all unlinked SeAT users
        $query = DB::table('users')->leftJoin('slack_users', 'id', '=', 'user_id')
                   ->whereNull('user_id');

        // if command has been run for a specific user, restrict result on it
        if ($this->job_payload->owner_id > 0) {
            $query->where( 'id', (int) $this->job_payload->owner_id );
            $this->writeInfoJobLog('Restricting job to single user : ' . $this->job_payload->owner_id);
        }

        $users = $query->get();

        $this->bindingSlackUser($users);

        $this->writeInfoJobLog('The full syncing process took ' .
            number_format(microtime(true) - $job_start, 2) . 's to complete.');

        $this->updateJobStatus([
            'status' => 'Done',
            'output' => null,
        ]);

        return;
    }

    private function bindingSlackUser($users)
    {
        foreach ($users as $user) {

        	try {

		        $response = $this->slack->setBody([
			        'email' => $user->email
		        ])->invoke('post', '/users.lookupByEmail');

		        SlackUser::create([
			        'user_id'  => $user->id,
			        'slack_id' => $response->user->id,
			        'name' => property_exists($response->user, 'name') ? $response->user->name : '',
		        ]);

		        sleep(1);

	        } catch (RequestFailedException $e) {

        		SlackLog::create([
        			'event' => 'sync',
			        'message' => sprintf('Unable to retrieve Slack user for user %s (%s)', $user->name, $user->email),
		        ]);

	        }

        }
    }

}
