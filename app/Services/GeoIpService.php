<?php

namespace App\Services;

use App\Models\City;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoIpService
{
    protected const API_URL = 'http://api.sypexgeo.net/json/';

    /**
     * Определяет город по IP-адресу.
     *
     * @param string|null $ip
     * @return City|null
     */
    public function getCityByIp(?string $ip): ?City
    {
        if (empty($ip) || in_array($ip, ['127.0.0.1', '::1'], true)) {
            return null;
        }

        try {
            $response = Http::get(self::API_URL . $ip);

            if ($response->failed()) {
                Log::error('Sypex Geo API request failed', [
                    'ip' => $ip,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();
            $cityName = $data['city']['name_ru'] ?? null;

            if (!$cityName) {
                return null;
            }

            // Ищем активный город в БД по имени, без учета регистра (совместимо с MySQL и PostgreSQL)
            return City::query()
                ->where('active', true)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($cityName)])
                ->first();

        } catch (\Throwable $e) {
            Log::error('Error in GeoIpService', [
                'ip' => $ip,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function debugCityByIp(?string $ip): array
    {
        $debugData = [
            'ip_checked' => $ip,
            'error' => null,
            'api_response_body' => null,
            'extracted_city_name' => null,
            'database_query' => null,
            'database_result' => 'Not Run',
        ];

        if (empty($ip) || in_array($ip, ['127.0.0.1', '::1'], true)) {
            $debugData['error'] = 'Local IP, lookup skipped.';
            return $debugData;
        }

        try {
            $response = Http::get(self::API_URL . $ip);
            $debugData['api_response_body'] = $response->json();

            if ($response->failed()) {
                $debugData['error'] = 'Sypex Geo API request failed with status: ' . $response->status();
                return $debugData;
            }

            $cityName = $debugData['api_response_body']['city']['name_ru'] ?? null;
            $debugData['extracted_city_name'] = $cityName;

            if (!$cityName) {
                $debugData['error'] = 'City name not found in API response.';
                return $debugData;
            }

            $query = City::query()
                ->where('active', true)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($cityName)]);

            $bindings = $query->getBindings();
            $sql = $query->toSql();

            $debugSql = $sql;
            foreach ($bindings as $binding) {
                $debugSql = preg_replace('/\?/', is_numeric($binding) ? $binding : "'" . $binding . "'", $debugSql, 1);
            }
            $debugData['database_query'] = $debugSql;

            $city = $query->first();

            if ($city) {
                $debugData['database_result'] = 'City Found: ' . $city->name . ' (ID: ' . $city->id . ')';
            } else {
                $debugData['database_result'] = 'City NOT Found in DB.';
            }
            return $debugData;

        } catch (\Throwable $e) {
            $debugData['error'] = 'Exception in GeoIpService: ' . $e->getMessage();
            return $debugData;
        }
    }
}
