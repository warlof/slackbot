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
     * @throws \Exception
     */
    public function up()
    {
        $lastUser = '';
        $users = SlackUser::all();

        foreach ($users as $user) {
            if ($user->slack_id == $lastUser) {
                $user->delete();
            }

            $lastUser = $user->slack_id;
        }

        Schema::table('slack_users', function (Blueprint $table) {
            $table->unique('slack_id');
        });
    }
}
