<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class WeatherController extends Controller
{
    // Единицы измерения по умолчанию - Цельсий
    private $units = 'metric';

    // Метод для получения погоды по городу
    public function getWeather(Request $request)
    {
        $request->validate([
            'city' => 'required|string',
        ]);

        $city = $request->input('city');
        $units = Cache::get('units', 'metric'); // Получаем единицы измерения из кеша

        try {
            $apiKey = env('OPENWEATHER_API_KEY');
            $response = Http::get("https://api.openweathermap.org/data/2.5/weather", [
                'q' => $city,
                'appid' => $apiKey,
                'units' => $units,
                'lang' => 'ru', // Для русского языка
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Сохраняем последний поиск
                $this->saveRecentSearch($city);

                return response()->json([
                    'city' => $data['name'],
                    'temperature' => $data['main']['temp'],
                    'weather' => $data['weather'][0]['description'],
                    'wind_speed' => $data['wind']['speed'],
                    'units' => $units == 'metric' ? 'Celsius' : 'Fahrenheit',
                ]);
            } else {
                return response()->json(['error' => 'City not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while fetching weather data'], 500);
        }
    }

    // Метод для переключения единиц измерения
    public function toggleUnits()
    {
        $currentUnits = Cache::get('units', 'metric');
        $newUnits = $currentUnits === 'metric' ? 'imperial' : 'metric';

        Cache::put('units', $newUnits, 3600); // Сохраняем на 1 час

        return response()->json(['units' => $newUnits === 'metric' ? 'Celsius' : 'Fahrenheit']);
    }

    // Метод для отображения недавних поисков
    public function recentSearches()
    {
        $recentSearches = Cache::get('recent_searches', []);

        return response()->json($recentSearches);
    }

    // Сохраняем последние 5 запросов
    private function saveRecentSearch($city)
    {
        $recentSearches = Cache::get('recent_searches', []);

        // Убираем дубликаты
        $recentSearches = array_filter($recentSearches, function ($search) use ($city) {
            return $search !== $city;
        });

        // Добавляем новый город в начало массива
        array_unshift($recentSearches, $city);

        // Оставляем только 5 последних
        $recentSearches = array_slice($recentSearches, 0, 5);

        Cache::put('recent_searches', $recentSearches, 3600); // Сохраняем на 1 час
    }
}
