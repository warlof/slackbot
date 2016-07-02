<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class UpdateSlackbotAllianceRelation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('slack_channel_alliances', function (Blueprint $table) {
            $table->foreign('alliance_id')
                ->references('allianceID')
                ->on('eve_alliance_lists')
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
        Schema::table('slack_channel_alliances', function (Blueprint $table) {
            $table->dropForeign('slack_channel_alliances_alliance_id_foreign');
        });
    }
}
