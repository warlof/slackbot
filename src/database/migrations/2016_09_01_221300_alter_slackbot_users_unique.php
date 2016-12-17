<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Warlof\Seat\Slackbot\Models\SlackUser;

class AlterSlackbotUsersUnique extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $last_user = '';
        $users = SlackUser::all();

        foreach ($users as $user) {
            if ($user->slack_id == $last_user) {
                $user->delete();

            }
            $last_user = $user->slack_id;
        }

        Schema::table('slack_users', function (Blueprint $table) {
            $table->unique('slack_id');
        });
    }
}
