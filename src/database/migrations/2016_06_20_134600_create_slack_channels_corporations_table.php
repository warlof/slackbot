<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSlackChannelsRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('slack_channels_roles', function (Blueprint $table) {
            $table->integer('role_id');
            $table->string('channel_id');
            $table->boolean('enable');
            $table->timestamps();
            
            $table->primary(['role_id', 'channel_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('slack_channels_roles');
    }
}
