<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSlackChannelsCorporationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('slack_channels_corporations', function (Blueprint $table) {
            $table->integer('corporation_id');
            $table->string('channel_id');
            $table->boolean('enable');
            $table->timestamps();
            
            $table->primary(['corporation_id', 'channel_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('slack_channels_corporations');
    }
}
