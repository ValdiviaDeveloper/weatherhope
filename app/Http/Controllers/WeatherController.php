<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherController extends Controller
{
    /**
     * Display the weather view.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('weather');
    }

    /**
     * Get the weather forecast for a given location from NASA POWER API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWeatherForecast(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $latitude = $validated['latitude'];
        $longitude = $validated['longitude'];
        $apiKey = config('app.nasa_api_key'); // We need to add this to config/app.php

        $response = Http::get('https://power.larc.nasa.gov/api/temporal/daily/point', [
            'parameters' => 'T2M,PRECTOTCORR',
            'community' => 'RE',
            'longitude' => $longitude,
            'latitude' => $latitude,
            'start' => date('Ymd', strtotime('-7 days')),
            'end' => date('Ymd'),
            'format' => 'JSON',
            'api_key' => $apiKey,
        ]);

        if ($response->failed()) {
            Log::error('NASA POWER API request failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return response()->json(['full_text' => 'Lo siento, no se pudo obtener el pronóstico en este momento.'], 500);
        }

        $data = $response->json();
        
        // The API returns -999 for missing data. We need to find the last valid data point.
        $lastTemp = -999;
        $lastPrecip = -999;

        if (isset($data['properties']['parameter']['T2M'])) {
            $temperatures = array_reverse($data['properties']['parameter']['T2M']);
            foreach ($temperatures as $temp) {
                if ($temp > -999) {
                    $lastTemp = round($temp);
                    break;
                }
            }
        }

        if (isset($data['properties']['parameter']['PRECTOTCORR'])) {
            $precipitations = array_reverse($data['properties']['parameter']['PRECTOTCORR']);
            foreach ($precipitations as $precip) {
                if ($precip > -999) {
                    // Precipitation is in mm. Let's convert it to a simple chance percentage for the voice response.
                    // This is a simplified conversion for the prototype.
                    $lastPrecip = min(round($precip * 10), 100); // Simple logic: 1mm = 10% chance, capped at 100%
                    break;
                }
            }
        }

        if ($lastTemp === -999) {
            return response()->json(['full_text' => 'No hay datos de temperatura disponibles para tu ubicación.']);
        }

        $responseText = "La temperatura más reciente para tu ubicación es de {$lastTemp} grados. ";
        if ($lastPrecip > 0) {
            $responseText .= "La probabilidad de precipitación es de aproximadamente {$lastPrecip} por ciento.";
        } else {
            $responseText .= "No se esperan precipitaciones.";
        }

        return response()->json([
            'full_text' => $responseText,
            'forecast' => [
                'temperature' => $lastTemp,
                'precipitation_chance' => $lastPrecip,
            ]
        ]);
    }

    /**
     * Get the air quality forecast for a given location from a NASA API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAirQualityForecast(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $latitude = $validated['latitude'];
        $longitude = $validated['longitude'];
        $apiKey = config('app.openweather_api_key');

        $response = Http::get('http://api.openweathermap.org/data/2.5/air_pollution', [
            'lat' => $latitude,
            'lon' => $longitude,
            'appid' => $apiKey,
        ]);

        if ($response->failed()) {
            Log::error('OpenWeatherMap Air Pollution API request failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return response()->json(['full_text' => 'Lo siento, no se pudo obtener la calidad del aire en este momento.'], 500);
        }

        $data = $response->json();

        if (!isset($data['list'][0]['main']['aqi'])) {
            return response()->json(['full_text' => 'No hay datos de calidad del aire disponibles para tu ubicación.']);
        }

        $aqi = $data['list'][0]['main']['aqi'];
        $components = $data['list'][0]['components'];

        $aqiText = "";
        switch ($aqi) {
            case 1: $aqiText = "Buena"; break;
            case 2: $aqiText = "Justa"; break;
            case 3: $aqiText = "Moderada"; break;
            case 4: $aqiText = "Pobre"; break;
            case 5: $aqiText = "Muy Pobre"; break;
            default: $aqiText = "Desconocida"; break;
        }

        $responseText = "La calidad del aire es {$aqiText} (AQI: {$aqi}). ";
        $responseText .= "Componentes principales: CO: {$components['co']} μg/m³, NO2: {$components['no2']} μg/m³, O3: {$components['o3']} μg/m³.";

        return response()->json([
            'full_text' => $responseText,
            'air_quality' => [
                'aqi' => $aqi,
                'components' => $components,
            ]
        ]);
    }

    /**
     * Get the hourly weather forecast for a given location from OpenWeatherMap One Call API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHourlyForecast(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $latitude = $validated['latitude'];
        $longitude = $validated['longitude'];
        $apiKey = config('app.openweather_api_key'); // Using the general OpenWeatherMap API key

        $response = Http::get('https://api.openweathermap.org/data/2.5/forecast', [
            'lat' => $latitude,
            'lon' => $longitude,
            'appid' => $apiKey,
            'units' => 'metric',
            'lang' => 'es',
        ]);

        if ($response->failed()) {
            Log::error('OpenWeatherMap 2.5 Forecast API request failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return response()->json(['full_text' => 'Lo siento, no se pudo obtener el pronóstico por hora en este momento.'], 500);
        }

        $data = $response->json();

        if (!isset($data['list'])) {
            return response()->json(['full_text' => 'No hay datos de pronóstico por hora disponibles para tu ubicación.']);
        }

        $hourlyForecasts = [];
        // Get next 8 forecast entries (each is 3 hours, so covers 24 hours)
        foreach (array_slice($data['list'], 0, 8) as $forecastData) {
            $hourlyForecasts[] = [
                'time' => date('H:i', $forecastData['dt']),
                'temp' => round($forecastData['main']['temp']),
                'description' => $forecastData['weather'][0]['description'],
                'icon' => $forecastData['weather'][0]['icon'],
            ];
        }

        $responseText = "Pronóstico por hora para las próximas 24 horas (cada 3 horas).";

        return response()->json([
            'full_text' => $responseText,
            'hourly_forecast' => $hourlyForecasts,
        ]);
    }
}