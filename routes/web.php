<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WeatherController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::get('/weather', [WeatherController::class, 'index'])->name('weather');
Route::post('/weather/likelihood', [WeatherController::class, 'getWeatherLikelihood'])->name('weather.likelihood');
Route::post('/weather/likelihood-text', [WeatherController::class, 'getWeatherLikelihoodByText'])->name('weather.likelihood.text');