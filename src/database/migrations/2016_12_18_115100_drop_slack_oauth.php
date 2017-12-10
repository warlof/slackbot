<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class DropSlackOauth extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('slack_oauth')) {
            Schema::drop('slack_oauth');
        }
    }

    public function down()
    {
        Schema::create('slack_oauth', function (Blueprint $table) {
            $table->string('client_id');
            $table->string('client_secret');
            $table->string('state')->nullable();
            $table->timestamps();

            $table->primary('client_id');
        });
    }
}
