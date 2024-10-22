<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class WeatherController extends Controller
{
    private $units = 'metric';

    public function getWeather(Request $request)
    {
        $request->validate([
            'city' => 'required|string',
        ]);

        $city = $request->input('city');
        $units = Cache::get('units', 'metric');

        try {
            $apiKey = env('OPENWEATHER_API_KEY');
            $response = Http::get("https://api.openweathermap.org/data/2.5/weather", [
                'q' => $city,
                'appid' => $apiKey,
                'units' => $units,
                'lang' => 'ru',
            ]);

            if ($response->successful()) {
                $data = $response->json();

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

    public function toggleUnits()
    {
        $currentUnits = Cache::get('units', 'metric');
        $newUnits = $currentUnits === 'metric' ? 'imperial' : 'metric';

        Cache::put('units', $newUnits, 3600);

        return response()->json(['units' => $newUnits === 'metric' ? 'Celsius' : 'Fahrenheit']);
    }

    public function recentSearches()
    {
        $recentSearches = Cache::get('recent_searches', []);

        return response()->json($recentSearches);
    }

    private function saveRecentSearch($city)
    {
        $recentSearches = Cache::get('recent_searches', []);

        $recentSearches = array_filter($recentSearches, function ($search) use ($city) {
            return $search !== $city;
        });

        array_unshift($recentSearches, $city);

        $recentSearches = array_slice($recentSearches, 0, 5);

        Cache::put('recent_searches', $recentSearches, 3600);
    }
}
