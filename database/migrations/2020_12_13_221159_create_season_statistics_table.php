<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSeasonStatisticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('season_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->references('id')->on('seasons')->cascadeOnDelete();
            $table->foreignId('calendar_id')->references('id')->on('season_calendars')->cascadeOnDelete();
            $table->foreignId('team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->integer('scored');
            $table->integer('missed');

//            $table->foreignId('first_team_id')->references('id')->on('teams')->cascadeOnDelete();
//            $table->foreignId('second_team_id')->references('id')->on('teams')->cascadeOnDelete();

//            $table->integer('first_score');
//            $table->integer('second_score');
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
        Schema::dropIfExists('season_statistics');
    }
}
