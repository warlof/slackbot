<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 09/12/2017
 * Time: 11:33
 */

namespace Warlof\Seat\Slackbot\Jobs;


use Illuminate\Support\Facades\DB;
use Monolog\Logger;
use Seat\Eveapi\Jobs\Base;
use stdClass;
use Warlof\Seat\Slackbot\Exceptions\SlackSettingException;
use Warlof\Seat\Slackbot\Models\SlackUser;
use Warlof\Seat\Slackbot\Repositories\Slack\Configuration;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackAuthentication;
use Warlof\Seat\Slackbot\Repositories\Slack\SlackApi;

class SyncUser extends Base {

	/**
	 * @var SlackApi
	 */
	private $slack;

	/**
	 * @var SlackAuthentication
	 */
	private $auth;

	public function handle() {

		if (!$this->trackOrDismiss())
			return;

		$this->updateJobStatus([
			'status' => 'Working',
		]);

		$this->writeInfoJobLog('Checking requirement and initializing job...');

		if (is_null(setting('warlof.slackbot.credentials.access_token', true)))
			throw new SlackSettingException("warlof.slackbot.credentials.access_token is missing in settings. " .
			                                "Ensure you've link SeAT to a valid Slack Team.");

		$configuration = Configuration::getInstance();
		$configuration->http_user_agent = '(Clan Daerie;Warlof Tutsimo;Daerie Inc.;Get Off My Lawn)';
		$configuration->logger_level = Logger::DEBUG;
		$configuration->logfile_location = storage_path('logs/slack.log');
		$configuration->file_cache_location = storage_path('cache/slack/');

		$this->slack = new SlackApi();
		$this->auth = new SlackAuthentication([
			'access_token' => setting('warlof.slackbot.credentials.access_token', true),
			'scopes' => [
				'users:read',
				'read',
				'users:read.email',
			],
		]);
		$this->slack->setAuthentication($this->auth);

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

		$this->fetchingSlackTeamMembers($users);

		$this->writeInfoJobLog('The full syncing process took ' .
			number_format(microtime(true) - $job_start, 2) . 's to complete.');

		$this->updateJobStatus([
			'status' => 'Done',
			'output' => null,
		]);

		return;
	}

	private function fetchingSlackTeamMembers($users, string $cursor = null)
	{
		if (!is_null($cursor))
			$this->slack->setQueryString([
				'cursor' => $cursor,
			]);

		$response = $this->slack->invoke('get', 'users.list');

		foreach ($users as $user) {

			foreach ($response->members as $member) {

				if (!$this->isActiveTeamMember($member))
					continue;

				if (!property_exists($member, 'profile'))
					continue;

				if (!property_exists($member->profile, 'email'))
					continue;

				if ($member->profile->email != $user->email)
					continue;

				SlackUser::create([
					'user_id' => $user->id,
					'slack_id' => $member->id,
				]);

			}

		}

		if (property_exists($response, 'response_metadata') && $response->response_metadata->next_cursor != '') {
			sleep(1);
			$this->fetchingSlackTeamMembers( $users, $response->response_metadata->next_cursor );
		}
	}

	private function isActiveTeamMember(stdClass $user) : bool
	{
		if ($user->deleted || $user->is_bot)
			return false;

		if (!property_exists($user, 'profile'))
			return false;

		return !property_exists($user->profile, 'api_app_id');
	}

}
