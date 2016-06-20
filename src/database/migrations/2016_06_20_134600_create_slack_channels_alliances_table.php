<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSlackChannelsAlliancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('slack_channels_alliances', function (Blueprint $table) {
            $table->integer('alliance_id');
            $table->string('channel_id');
            $table->boolean('enable');
            $table->timestamps();
            
            $table->primary(['alliance_id', 'channel_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('slack_channels_alliances');
    }
}
