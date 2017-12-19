<?php

/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 19/12/2017
 * Time: 16:08
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Seat\Services\Models\Schedule;

class ScheduleSeederV230 extends Migration
{
	protected $schedule = [
		[
			'command'    => 'slack:user:sync',
			'expression' => '*/15 * * * *',
			'allow_overlap'     => false,
			'allow_maintenance' => false,
			'ping_before'       => 'https://slack.com',
			'ping_after'        => null,
		],
		[
			'command'    => 'slack:user:invite',
			'expression' => '*/10 * * * *',
			'allow_overlap'     => false,
			'allow_maintenance' => false,
			'ping_before'       => 'https://slack.com',
			'ping_after'        => null,
		],
		[
			'command'    => 'slack:user:kick',
			'expression' => '*/10 * * * *',
			'allow_overlap'     => false,
			'allow_maintenance' => false,
			'ping_before'       => 'https://slack.com',
			'ping_after'        => null,
		]
	];

	public function up()
	{
		Schedule::where('command', 'slack:update')->delete();

		foreach ($this->schedule as $job) {
			$existing = DB::table('schedules')
			              ->where('command', $job['command'])
			              ->first();

			if ($existing) {
				$existing->update([
					'expression' => $job['expression'],
				]);
			}

			if (!$existing) {
				DB::table('schedules')->insert($job);
			}
		}
	}
}