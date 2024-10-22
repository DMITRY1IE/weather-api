<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WeatherController;

//Поиск погоды (GET-запрос с параметром city)
Route::get('/weather', [WeatherController::class, 'getWeather']);
//Переключение единиц измерения (GET-запрос)
Route::get('/units', [WeatherController::class, 'toggleUnits']);
//Просмотр последних поисков
Route::get('/recent-searches', [WeatherController::class, 'recentSearches']);

