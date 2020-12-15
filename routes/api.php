<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('start-season', \App\Http\Controllers\SimulationController::class . '@startSeason');

Route::get('next-stage/{season}', \App\Http\Controllers\SimulationController::class . '@nextStage');
Route::get('finish-season/{season}',\App\Http\Controllers\SimulationController::class . '@finishSeason');
