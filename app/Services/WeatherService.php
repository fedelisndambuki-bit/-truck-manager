<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    private const BASE_URL = 'https://api.open-meteo.com/v1/forecast';

    /**
     * Fetch current weather conditions for a site's coordinates via the free Open-Meteo API.
     * Returns null on any failure so callers can fall back to manual entry.
     */
    public function fetchCurrent(float $lat, float $lng): ?array
    {
        try {
            $response = Http::timeout(5)->get(self::BASE_URL, [
                'latitude' => $lat,
                'longitude' => $lng,
                'current' => 'temperature_2m,relative_humidity_2m,wind_speed_10m,precipitation,weather_code',
                'timezone' => 'auto',
            ]);

            if (! $response->successful()) {
                Log::warning('WeatherService: Open-Meteo request failed', ['status' => $response->status()]);

                return null;
            }

            $current = $response->json('current');

            if (! $current) {
                return null;
            }

            return [
                'temperature_high' => $current['temperature_2m'] ?? null,
                'temperature_low' => $current['temperature_2m'] ?? null,
                'conditions' => $this->mapWeatherCode((int) ($current['weather_code'] ?? -1)),
                'rainfall_mm' => $current['precipitation'] ?? null,
                'wind_speed_kph' => $current['wind_speed_10m'] ?? null,
                'humidity_pct' => $current['relative_humidity_2m'] ?? null,
                'weather_source' => 'auto',
                'weather_fetched_at' => now(),
            ];
        } catch (\Throwable $e) {
            Log::warning('WeatherService: fetchCurrent failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Map WMO weather interpretation codes to a human-readable condition string.
     */
    private function mapWeatherCode(int $code): string
    {
        return match (true) {
            $code === 0 => 'Clear Sky',
            in_array($code, [1, 2, 3]) => 'Partly Cloudy',
            in_array($code, [45, 48]) => 'Fog',
            in_array($code, [51, 53, 55, 56, 57]) => 'Drizzle',
            in_array($code, [61, 63, 65, 66, 67, 80, 81, 82]) => 'Rain',
            in_array($code, [71, 73, 75, 77, 85, 86]) => 'Snow',
            in_array($code, [95, 96, 99]) => 'Thunderstorm',
            default => 'Unknown',
        };
    }
}
