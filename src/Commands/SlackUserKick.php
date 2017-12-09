<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 09/12/2017
 * Time: 18:46
 */

namespace Warlof\Seat\Slackbot\Commands;


use Illuminate\Console\Command;
use Seat\Eveapi\Helpers\JobPayloadContainer;
use Seat\Eveapi\Traits\JobManager;
use Seat\Services\Helpers\AnalyticsContainer;
use Seat\Services\Jobs\Analytics;
use Warlof\Seat\Slackbot\Jobs\AssKicker;

class SlackUserKick extends Command {

	use JobManager;

	protected $signature = 'slack:user:kick {user_id? : The id of a SeAT user}';

	protected $description = 'Fire a job which will kick SeAT user from Slack channels according to your policy.';

	public function handle(JobPayloadContainer $container)
	{
		$container->api = 'Slack';
		$container->scope = 'Kick';
		$container->owner_id = 0;

		if ($this->hasArgument('user_id'))
			$container->owner_id = intval($this->argument('user_id'));

		$job_id = $this->addUniqueJob(AssKicker::class, $container);

		$this->info('Job ' . $job_id . ' dispatched!');

		dispatch((new Analytics((new AnalyticsContainer())
			->set('type', 'event')
			->set('ec', 'queues')
			->set('ea', 'queue_tokens')
			->set('el', 'console')
			->set('ev', 1)))
		->onQueue('high'));
	}

}