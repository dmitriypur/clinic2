<?php

namespace App\Services;

use App\Models\City;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoIpService
{
    protected const API_URL = 'https://api.sypexgeo.net/json/';

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
}
