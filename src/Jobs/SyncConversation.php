<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 18/12/2017
 * Time: 11:07
 */

namespace Warlof\Seat\Slackbot\Jobs;


use Warlof\Seat\Slackbot\Models\SlackChannel;

class SyncConversation extends AbstractSlackJob {

	public function handle()
	{
		if (!$this->trackOrDismiss())
			return;

		$this->updateJobStatus([
			'status' => 'Working',
		]);

		$this->writeInfoJobLog('Starting Slack Sync Conversation...');

		$job_start = microtime(true);

		$conversations = $this->fetchConversations();
		$conversations_buffer = [];

		foreach ($conversations as $conversation) {

			$conversations_buffer[] = $conversation->id;
			SlackChannel::updateOrCreate([
					'id' => $conversation->id,
				],
				[
					'name' => $conversation->name,
					'is_group' => $conversation->is_group,
					'is_general' => $conversation->is_general,
				]);

		}

		SlackChannel::whereNotIn('id', $conversations_buffer)->delete();

		$this->writeInfoJobLog('The full syncing process took ' .
			number_format(microtime(true) - $job_start, 2) . 's to complete.');

		$this->updateJobStatus([
			'status' => 'Done',
			'output' => null,
		]);

		return;
	}

	private function fetchConversations(string $cursor = null) : array
	{
		$this->slack->setQueryString([
			'types' => implode(',', [
				'public_channel',
				'private_channel',
			]),
			'exclude_archived' => true,
		]);

		if (!is_null($cursor))
			$this->slack->setQueryString([
				'cursor' => $cursor,
				'types' => implode(',', [
					'public_channel',
					'private_channel',
				]),
				'exclude_archived' => true,
			]);

		$response = $this->slack->invoke('get', '/conversations.list');
		$conversations = $response->channels;

		if (property_exists($response, 'response_metadata') && $response->response_metadata->next_cursor != '') {
			sleep(1);
			$conversations = array_merge(
				$conversations,
				$this->fetchConversations($response->response_metadata->next_cursor)
			);
		}

		return $conversations;
	}

}
