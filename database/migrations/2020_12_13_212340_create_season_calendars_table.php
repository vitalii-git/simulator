<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSeasonCalendarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('season_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->references('id')->on('seasons')->cascadeOnDelete();
            $table->foreignId('first_team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->foreignId('second_team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->integer('stage');
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
        Schema::dropIfExists('season_calendars');
    }
}
