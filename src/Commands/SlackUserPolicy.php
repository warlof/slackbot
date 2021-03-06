<?php
/**
 * This file is part of slackbot and provide user synchronization between both SeAT and a Slack Team
 *
 * Copyright (C) 2016, 2017, 2018, 2019  Loïc Leuilliot <loic.leuilliot@gmail.com>
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
use Warlof\Seat\Slackbot\Jobs\ConversationDispatcher;

class SlackUserPolicy extends Command
{

    /**
     * @var string
     */
    protected $signature = 'slack:user:policy';

    /**
     * @var string
     */
    protected $description = 'Fire a job which will invite or kick SeAT user into Slack channels according to your policy.';

    /**
     * Execute the console command
     */
    public function handle()
    {
        ConversationDispatcher::dispatch()->onQueue('high');

        $this->info('A job has been queued in order to invite and kick user on granted channels.');
    }

}
