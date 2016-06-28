<?php

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
            $table->unsignedInteger('user_id');
            $table->string('slack_id');
            $table->boolean('invited');
            $table->timestamps();

            $table->primary('user_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
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
            $table->unsignedInteger('user_id');
            $table->string('channel_id');
            $table->boolean('enable');
            $table->timestamps();

            $table->primary(['user_id', 'channel_id']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
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
        Schema::drop('slack_groups');
        Schema::drop('slack_channels');
    }
}
