<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSlackbotChannelTitle extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('slack_channel_titles', function (Blueprint $table) {
            $table->bigInteger('corporation_id');
            $table->bigInteger('title_id');
            $table->integer('title_surrogate_key');
            $table->string('channel_id');
            $table->boolean('enable');
            $table->timestamps();

            $table->primary(['corporation_id', 'title_id', 'channel_id']);
        });
    }

    public function down()
    {
        Schema::table('slack_channel_titles', function (Blueprint $table){
            $table->drop();
        });
    }
}
