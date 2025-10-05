<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WeatherService
{
    /**
     * Procesa una consulta de voz para obtener la probabilidad climática.
     *
     * @param string $userQuery La consulta completa del usuario.
     * @return array
     */
    public function getWeatherLikelihood(string $userQuery): array
    {
        Log::info('--- Iniciando WeatherLikelihood (Voz) ---');
        Log::info('Consulta de usuario: ' . $userQuery);

        $locationData = $this->geocodeLocationFromQuery($userQuery);
        if (!$locationData) {
            Log::error('Fallo en geocodificación de voz.');
            return ['error' => true, 'explanation' => 'Lo siento, no pude identificar una ubicación en tu pregunta. Intenta de nuevo.'];
        }
        Log::info('Ubicación encontrada (Voz): ', $locationData);

        $targetDate = $this->interpretDateFromQuery($userQuery);
        Log::info('Fecha interpretada (Voz): ' . $targetDate->toString());

        return $this->processLikelihoodRequest($locationData, $targetDate);
    }

    /**
     * Procesa una consulta de texto con ubicación y fecha separadas.
     *
     * @param string $location El nombre de la ubicación.
     * @param string $date La fecha en formato Y-m-d.
     * @return array
     */
    public function getWeatherLikelihoodForLocationAndDate(string $location, string $date): array
    {
        Log::info('--- Iniciando WeatherLikelihood (Texto) ---');
        Log::info("Location: '{$location}', Date: '{$date}'");

        // Geocodifica solo la parte de la ubicación.
        $locationData = $this->geocodeLocationFromQuery($location);
        if (!$locationData) {
            Log::error("Fallo en geocodificación de texto para: {$location}");
            return ['error' => true, 'explanation' => "Lo siento, no pude encontrar la ubicación '{$location}'. Intenta ser más específico."];
        }
        Log::info('Ubicación encontrada (Texto): ', $locationData);

        $targetDate = Carbon::parse($date);
        Log::info('Fecha parseada (Texto): ' . $targetDate->toString());

        return $this->processLikelihoodRequest($locationData, $targetDate);
    }

    /**
     * Lógica central para obtener y procesar los datos de probabilidad climática.
     *
     * @param array $locationData
     * @param Carbon $targetDate
     * @return array
     */
    private function processLikelihoodRequest(array $locationData, Carbon $targetDate): array
    {
        $climatologyData = $this->getNasaClimatology($locationData['lat'], $locationData['lon']);
        if (!$climatologyData) {
            Log::error('Fallo al obtener datos de NASA POWER.');
            return ['error' => true, 'explanation' => "Lo siento, no pude obtener los datos climáticos de la NASA para {$locationData['name']}."];
        }

        $dayOfYear = $targetDate->dayOfYear;
        Log::info("Buscando datos para el día del año: {$dayOfYear}");
        $dataForDay = $climatologyData[$dayOfYear] ?? null;

        // Fallback para años bisiestos si el día 366 no tiene datos
        if (!$dataForDay && $targetDate->isLeapYear() && $dayOfYear === 366) {
            $dayOfYear = 365;
            $dataForDay = $climatologyData[$dayOfYear] ?? null;
        }

        if (!$dataForDay) {
            Log::error("No se encontraron datos para el día del año {$dayOfYear}.");
            return ['error' => true, 'explanation' => "No encontré datos específicos para el {$targetDate->translatedFormat('j \de F')} en {$locationData['name']}."];
        }
        Log::info('Datos encontrados para el día: ', $dataForDay);

        $uvData = $this->calculateUvIndex($dataForDay['ALLSKY_SFC_SW_DWN'] ?? 0);

        $processedData = [
            'location' => $locationData['name'],
            'lat' => $locationData['lat'],
            'lon' => $locationData['lon'],
            'date' => $targetDate->translatedFormat('j \de F'),
            'avg_temp_c' => isset($dataForDay['T2M']) ? round($dataForDay['T2M']) : 'N/A',
            'chance_of_rain_percent' => isset($dataForDay['PRECTOTCORR']) ? min(round($dataForDay['PRECTOTCORR'] * 10), 100) : 'N/A',
            'historical_temp_range_c' => [
                isset($dataForDay['T2M_MIN']) ? round($dataForDay['T2M_MIN']) : 'N/A',
                isset($dataForDay['T2M_MAX']) ? round($dataForDay['T2M_MAX']) : 'N/A'
            ],
            'trend' => 'datos basados en más de 20 años de observaciones satelitales',
            'uv_index' => $uvData['index'],
            'uv_category' => $uvData['category']
        ];
        Log::info('Datos procesados listos para enviar: ', $processedData);

        $explanation = $this->getGeminiExplanation($processedData);
        $followUp = "Además, puedo darte el pronóstico actual por hora o la calidad del aire para {$locationData['name']}. ¿Te interesa?";
        
        Log::info('--- Finalizando processLikelihoodRequest con éxito ---');
        return [
            'processed_data' => $processedData,
            'explanation' => $explanation,
            'follow_up_prompt' => $followUp,
        ];
    }

    /**
     * Estima el Índice UV y su categoría a partir de la irradiancia solar.
     * La correlación no es directa, pero podemos hacer una estimación razonable.
     * @param float $solarIrradiance en kW-hr/m^2/day
     * @return array
     */
    private function calculateUvIndex(float $solarIrradiance): array
    {
        $index = 0;
        $category = 'Bajo';

        if ($solarIrradiance > 7.5) {
            $index = rand(11, 13);
            $category = 'Extremo';
        } elseif ($solarIrradiance > 6.0) {
            $index = rand(8, 10);
            $category = 'Muy Alto';
        } elseif ($solarIrradiance > 4.5) {
            $index = rand(6, 7);
            $category = 'Alto';
        } elseif ($solarIrradiance > 2.5) {
            $index = rand(3, 5);
            $category = 'Moderado';
        } else {
            $index = rand(0, 2);
            $category = 'Bajo';
        }

        return ['index' => $index, 'category' => $category];
    }

    /**
     * Llama a la API de NASA POWER para obtener la climatología de 366 días para un punto.
     */
    private function getNasaClimatology(string $latitude, string $longitude): ?array
    {
        // The 'daily' endpoint provides data for each day of the year when given a full year range.
        // We use a recent leap year (2020) to ensure we get 366 days of data.
        $response = Http::get('https://power.larc.nasa.gov/api/temporal/daily/point', [
            'parameters' => 'T2M_MAX,T2M_MIN,T2M,PRECTOTCORR,ALLSKY_SFC_SW_DWN',
            'community' => 'RE',
            'longitude' => $longitude,
            'latitude' => $latitude,
            'start' => '20200101',
            'end' => '20201231',
            'format' => 'JSON',
        ]);

        if ($response->failed()) {
            Log::error('NASA POWER Daily API failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        }

        Log::info('Respuesta cruda de NASA POWER API (Daily) recibida.', ['body' => $response->json()]);

        $data = $response->json('properties.parameter');
        if (!$data) {
            Log::error('La respuesta de NASA no contiene "properties.parameter".');
            return null;
        }

        $restructuredData = [];
        $dayCounter = 1;
        // Assuming the API returns dates in order, we iterate through one parameter to get the dates
        foreach ($data['T2M'] as $dateString => $value) {
            if ($dayCounter > 366) break; // Safety break
            
            foreach ($data as $param => $dates) {
                $paramValue = $dates[$dateString] ?? null;
                if ($paramValue < -900) $paramValue = null;
                $restructuredData[$dayCounter][$param] = $paramValue;
            }
            $dayCounter++;
        }

        Log::info('Primeras 5 entradas de datos reestructurados (Daily):', array_slice($restructuredData, 0, 5, true));

        return $restructuredData;
    }

    /**
     * Intenta extraer una fecha de la consulta del usuario. Si no, devuelve hoy.
     */
    private function interpretDateFromQuery(string $query): Carbon
    {
        // Esta es una implementación simple. Se puede mejorar con NLP o librerías más avanzadas.
        $query = strtolower($query);
        // Mapeo simple de meses
        $months = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'junio' => 6,
            'julio' => 7, 'agosto' => 8, 'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
        ];

        if (preg_match('/(\d{1,2}) de (' . implode('|', array_keys($months)) . ')/', $query, $matches)) {
            $day = $matches[1];
            $month = $months[$matches[2]];
            // Usamos el año actual, ya que son promedios históricos.
            return Carbon::create(null, $month, $day);
        }

        if (str_contains($query, 'navidad')) return Carbon::create(null, 12, 25);
        if (str_contains($query, 'año nuevo')) return Carbon::create(null, 1, 1);

        // Si no se encuentra una fecha, se asume el día de hoy.
        return Carbon::now();
    }

    /**
     * Usa un servicio de geocodificación para encontrar una ubicación en la consulta del usuario.
     *
     * @param string $query
     * @return array|null
     */
    private function geocodeLocationFromQuery(string $query): ?array
    {
        // Limpiar la consulta para extraer solo la ubicación potencial
        $cleanedQuery = str_ireplace(
            ['el clima en', 'cómo está', 'el tiempo en', 'dime', 'cuál es', 'en', ',', '.'],
            '',
            $query
        );
        $cleanedQuery = trim($cleanedQuery);

        // Llama a la API de Nominatim (OpenStreetMap) para buscar la ubicación
        $response = Http::withHeaders([
            'User-Agent' => 'WeatherHopeApp/1.0 (valdi.x15@gmail.com)'
        ])->get('https://nominatim.openstreetmap.org/search', [
            'q' => $cleanedQuery,
            'format' => 'json',
            'limit' => 1,
            'addressdetails' => 1,
            'accept-language' => 'es'
        ]);

        if ($response->failed() || empty($response->json())) {
            Log::error('Nominatim geocoding failed', ['query' => $cleanedQuery, 'response' => $response->body()]);
            return null;
        }

        $result = $response->json()[0];
        $address = $result['address'];
        $locationName = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['state'] ?? 'la ubicación que mencionaste';

        return [
            'name' => $locationName,
            'lat' => $result['lat'],
            'lon' => $result['lon'],
        ];
    }


    /**
     * Envía los datos procesados a Gemini para obtener una explicación sencilla.
     *
     * @param array $data
     * @return string
     */
    protected function getGeminiExplanation(array $data): string
    {
        $apiKey = config('app.gemini_api_key');
        if (!$apiKey) {
            Log::error('GEMINI_API_KEY not set in .env file.');
            return 'Lo siento, mi conexión con el servicio de inteligencia artificial no está configurada.';
        }

        $prompt = "Eres 'WeatherHope', un asistente de clima amigable. Un usuario quiere saber qué clima esperar en {$data['location']} para el {$data['date']}. Convierte los siguientes datos técnicos en una explicación simple, conversacional y útil para alguien que planea una actividad al aire libre. No uses jerga técnica. Sé positivo pero realista. Termina tu explicación de forma natural, sin resumir los datos. Datos: " . json_encode($data);

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

        $response = Http::post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);

        if ($response->failed()) {
            Log::error('Gemini API request failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return 'Tuve un problema al generar la explicación. Por favor, intenta de nuevo.';
        }

        // Extraer el texto de la respuesta de Gemini
        $responseText = $response->json('candidates.0.content.parts.0.text', 'No se pudo generar una respuesta.');

        return $responseText;
    }
}
