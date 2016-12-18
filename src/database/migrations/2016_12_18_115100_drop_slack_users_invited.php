<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Warlof\Seat\Slackbot\Models\SlackUser;

class DropSlackUsersInvited extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('slack_users', function (Blueprint $table) {
            if (Schema::hasColumn('slack_users', 'invited')) {
                $table->dropColumn('invited');
            }
        });
    }

    public function down()
    {
        Schema::table('slack_users', function (Blueprint $table){
            $table->boolean('invited');
        });

        $users = SlackUser::all();

        foreach ($users as $user) {
            $user->update(['invited' => true]);
        }
    }
}
