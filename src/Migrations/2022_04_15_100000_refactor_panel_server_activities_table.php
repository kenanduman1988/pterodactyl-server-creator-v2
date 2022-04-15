<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorPanelServerActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //set index on node for pessimistic locking
        Schema::table('panel_servers', function (Blueprint $table) {
            $table->index('panel_node_id');
        });

        //add match id into activities
        Schema::table('panel_server_activities', function (Blueprint $table) {
            $table->unsignedBigInteger('match_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
