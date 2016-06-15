<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSlackChannelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function create()
    {
        Schema::table('slack_channel', function (Blueprint $table) {
            $table->string('id');
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('slack_channel');
    }
}
