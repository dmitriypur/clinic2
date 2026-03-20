<?php

namespace App\Services;

use App\Models\BookingWidgetBranchOrder;
use Illuminate\Support\Facades\DB;

class BookingWidgetOrderingService
{
    public function getDoctorOrderMapForCity(?int $cityId): array
    {
        if (! $cityId) {
            return [];
        }

        return DB::table('doctors')
            ->select(['doctors.uuid', 'city_doctor.sort_order'])
            ->join('city_doctor', 'city_doctor.doctor_id', '=', 'doctors.id')
            ->where('city_doctor.city_id', $cityId)
            ->whereNotNull('doctors.uuid')
            ->whereNotNull('city_doctor.sort_order')
            ->get()
            ->mapWithKeys(static function (object $doctor): array {
                return [mb_strtolower((string) $doctor->uuid) => (int) $doctor->sort_order];
            })
            ->all();
    }

    public function getBranchOrderMapForCity(?int $cityId): array
    {
        if (! $cityId) {
            return [];
        }

        return BookingWidgetBranchOrder::query()
            ->where('city_id', $cityId)
            ->whereNotNull('sort_order')
            ->orderBy('clinic_id')
            ->orderBy('sort_order')
            ->get(['clinic_id', 'branch_id', 'sort_order'])
            ->groupBy('clinic_id')
            ->map(static function ($rows): array {
                return $rows
                    ->mapWithKeys(static fn (BookingWidgetBranchOrder $row): array => [
                        (string) $row->branch_id => (int) $row->sort_order,
                    ])
                    ->all();
            })
            ->all();
    }
}
