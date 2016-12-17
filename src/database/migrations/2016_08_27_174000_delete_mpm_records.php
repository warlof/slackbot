<?php

use Illuminate\Database\Migrations\Migration;
use Warlof\Seat\Slackbot\Models\SlackChannel;

class DeleteMpmRecords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        SlackChannel::where('name', 'LIKE', 'mpdm-%')
            ->where('is_group', true)
            ->delete();
    }
}
