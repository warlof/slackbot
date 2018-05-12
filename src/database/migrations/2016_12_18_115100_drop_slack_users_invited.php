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

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Warlof\Seat\Slackbot\Models\SlackUser;

class DropSlackUsersInvited extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('slack_users', function (Blueprint $table) {
            if (Schema::hasColumn('slack_users', 'invited')) {
                $table->dropColumn('invited');
            }
        });
    }

    public function down()
    {
        Schema::table('slack_users', function (Blueprint $table){
            $table->boolean('invited');
        });

        $users = SlackUser::all();

        foreach ($users as $user) {
            $user->update(['invited' => true]);
        }
    }
}
