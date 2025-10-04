<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WeatherController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/weather', [WeatherController::class, 'getWeatherForecast']);
Route::post('/air-quality', [WeatherController::class, 'getAirQualityForecast']);
Route::post('/hourly-weather', [WeatherController::class, 'getHourlyForecast']);