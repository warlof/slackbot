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

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateSlackbotTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('slack_channels', function (Blueprint $table) {
            $table->string('id');
            $table->string('name');
            $table->boolean('is_group')->default(false);
            $table->timestamps();
            
            $table->primary('id');
        });

        Schema::create('slack_users', function (Blueprint $table) {
            $table->unsignedInteger('group_id');
            $table->string('slack_id');
            $table->boolean('invited');
            $table->timestamps();

            $table->primary('group_id');

            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->onDelete('cascade');
        });

        Schema::create('slack_channel_alliances', function (Blueprint $table) {
            $table->integer('alliance_id');
            $table->string('channel_id');
            $table->boolean('enable');
            $table->timestamps();

            $table->primary(['alliance_id', 'channel_id']);

            $table->foreign('channel_id')
                ->references('id')
                ->on('slack_channels')
                ->onDelete('cascade');
        });

        Schema::create('slack_channel_corporations', function (Blueprint $table) {
            $table->integer('corporation_id');
            $table->string('channel_id');
            $table->boolean('enable');
            $table->timestamps();

            $table->primary(['corporation_id', 'channel_id']);

            $table->foreign('channel_id')
                ->references('id')
                ->on('slack_channels')
                ->onDelete('cascade');
        });

        Schema::create('slack_channel_roles', function (Blueprint $table) {
            $table->unsignedInteger('role_id');
            $table->string('channel_id');
            $table->boolean('enable');
            $table->timestamps();

            $table->primary(['role_id', 'channel_id']);

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

            $table->foreign('channel_id')
                ->references('id')
                ->on('slack_channels')
                ->onDelete('cascade');
        });

        Schema::create('slack_channel_users', function (Blueprint $table) {
            $table->unsignedInteger('group_id');
            $table->string('channel_id');
            $table->boolean('enable');
            $table->timestamps();

            $table->primary(['group_id', 'channel_id']);

            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->onDelete('cascade');

            $table->foreign('channel_id')
                ->references('id')
                ->on('slack_channels')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('slack_channel_alliances');
        Schema::drop('slack_channel_corporations');
        Schema::drop('slack_channel_roles');
        Schema::drop('slack_channel_users');
        Schema::drop('slack_users');
        Schema::drop('slack_channels');
    }
}
