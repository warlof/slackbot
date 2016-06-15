<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSlackUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function create()
    {
        Schema::table('slack_user', function (Blueprint $table) {
            $table->integer('user_id');
            $table->string('slack_id');
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
        Schema::table('slack_user');
    }
}
