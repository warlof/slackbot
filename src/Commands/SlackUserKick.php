<?php
/**
 * This file is part of slackbot and provide user synchronization between both SeAT and a Slack Team
 *
 * Copyright (C) 2016, 2017, 2018  Loïc Leuilliot <loic.leuilliot@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Warlof\Seat\Slackbot\Commands;

use Illuminate\Console\Command;
use Seat\Web\Models\Group;
use Seat\Web\Models\User;
use Warlof\Seat\Slackbot\Jobs\AssKicker;

class SlackUserKick extends Command {

    /**
     * @var string
     */
    protected $signature = 'slack:user:kick {--group_ids= : The id list of SeAT user group (using , as separator)}' .
                                           '{--user_ids= : The id list of SeAT user (using , as separator)}';

    /**
     * @var string
     */
    protected $description = 'Fire a job which will kick SeAT user from Slack channels according to your policy.';

    /**
     * Execute the console command
     */
    public function handle()
    {
        $group_ids = [];
        $filtered = false;

        $job = new AssKicker();

        if ($this->option('user_ids')) {
            // update filter flag so we know that user has used some optional arguments
            $filtered = true;
            // transform the argument list in an array
            $ids = explode(',', $this->option('user_ids'));

            // retrieve all user which are in the filter
            $users = User::whereIn('id', $ids)->get();

            // retrieve related user group
            $group_ids = $users->each(function ($user) {
                if ($user->groups->count() > 0)
                    return $user->groups->first()->id;
                return 0;
            })->flatten()->toArray();
        }

        if ($this->option('group_ids')) {
            // update filter flag so we know that user has used some optional arguments
            $filtered = true;
            // transform the argument list in an array
            $ids = explode(',', $this->option('group_ids'));

            // retrieve all group which are in the filter and merge with the group ID list
            $group_ids = array_merge(Group::whereIn('id', $ids)->select('id')->get()->toArray());
        }

        // in case the user has specified some parameter, send the group ID list to the job
        if ($filtered)
            $job->setSeatGroupId($group_ids);

        // if the group ID list is empty and filter has been applied, abort the command
        if ($filtered && count($group_ids) < 1) {
            $this->error('Filled parameter returned no match !');
            return;
        }

        $job::dispatch();

        $this->info('A job has been queued in order to kick user from denied channels.');
    }

}