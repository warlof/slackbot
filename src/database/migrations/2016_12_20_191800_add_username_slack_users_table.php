<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddUsernameSlackUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('slack_users', function (Blueprint $table) {
            $table->string('name')->nullable()
                ->after('slack_id');
        });
    }

    public function down()
    {
        Schema::table('slack_users', function (Blueprint $table){
            $table->dropColumn('name');
        });
    }
}
