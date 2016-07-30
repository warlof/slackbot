<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateSlackbotPublic extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('slack_channel_public', function (Blueprint $table) {
            $table->string('channel_id');
            $table->boolean('enable');
            $table->timestamps();

            $table->primary('channel_id');
            
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
        Schema::drop('slack_channel_public');
    }
}
