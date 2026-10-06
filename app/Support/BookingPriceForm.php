<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class BookingPriceForm
{
    public static function hydrateCity(array $data): array
    {
        foreach ($data['branches'] ?? [] as $key => $branch) {
            $data['branches'][$key] = BookingPriceSchedule::hydrate($branch);
            $data['branches'][$key]['_booking_price_source'] = self::branchFingerprint($branch);
        }

        return $data;
    }

    public static function saveCity(array $data, array $originalBranches): array
    {
        $originalByHash = [];
        foreach ($originalBranches as $branch) {
            $originalByHash[self::branchFingerprint($branch)] = $branch;
        }

        foreach ($data['branches'] ?? [] as $key => $branch) {
            $hash = $branch['_booking_price_source'] ?? null;
            if ($hash !== null && ! array_key_exists($hash, $originalByHash)) {
                throw ValidationException::withMessages([
                    "data.branches.{$key}._booking_price_editor.price.amount" => 'Данные филиала уже изменились. Обновите страницу и повторите сохранение.',
                ]);
            }
            unset($branch['_booking_price_source']);
            $data['branches'][$key] = BookingPriceSchedule::save($branch, $hash ? $originalByHash[$hash] : [], "data.branches.{$key}", legacyApiFallback: true);
        }
        if (array_key_exists('branches', $data)) {
            $data['branches'] = array_values($data['branches'] ?? []);
        }

        return $data;
    }

    private static function branchFingerprint(array $branch): string
    {
        return hash('sha256', json_encode($branch, JSON_THROW_ON_ERROR));
    }
}
