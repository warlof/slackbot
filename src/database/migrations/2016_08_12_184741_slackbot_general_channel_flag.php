<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class SlackbotGeneralChannelFlag extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('slack_channels', function (Blueprint $table) {
            $table->boolean('is_general')
                ->after('is_group')
                ->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('slack_channels', function (Blueprint $table) {
            $table->dropColumn('is_general');
        });
    }
}
