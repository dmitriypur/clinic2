<?php

namespace App\Services;

use App\Models\BookingWidgetBranchOrder;
use App\Models\City;
use Carbon\CarbonInterface;

class BookingWidgetBranchSyncService
{
    private const REFRESH_TTL_MINUTES = 15;

    public function __construct(
        private readonly BookingWidgetApiService $bookingWidgetApiService,
    ) {
    }

    public function syncCity(int $localCityId): void
    {
        $city = City::query()->findOrFail($localCityId);

        if (! $this->shouldRefresh($city->id)) {
            return;
        }

        $bookingCityId = $this->resolveBookingCityId($city);

        if (! $bookingCityId) {
            return;
        }

        $clinics = collect($this->bookingWidgetApiService->extractList(
            $this->bookingWidgetApiService->getClinicsByCity($bookingCityId)
        ))
            ->filter(fn (array $clinic): bool => $this->isClinicAllowed((int) data_get($clinic, 'id')))
            ->values();

        $branchesByClinicId = $this->bookingWidgetApiService->getClinicBranchesBatch(
            $clinics->pluck('id')->all(),
            $bookingCityId
        );

        $freshBranchKeys = [];
        $timestamp = now();
        $upsertRows = [];

        foreach ($clinics as $clinic) {
            $clinicId = (int) data_get($clinic, 'id');
            $clinicName = (string) data_get($clinic, 'name', '');

            $branches = $this->bookingWidgetApiService->extractList(
                $branchesByClinicId[$clinicId] ?? []
            );

            foreach ($branches as $branch) {
                $branchId = (int) data_get($branch, 'id');

                if (! $branchId) {
                    continue;
                }

                $freshBranchKeys[] = $this->makeBranchKey($clinicId, $branchId);

                $upsertRows[] = [
                    'city_id' => $city->id,
                    'clinic_id' => $clinicId,
                    'branch_id' => $branchId,
                    'clinic_name' => $clinicName,
                    'title' => (string) data_get($branch, 'name', data_get($branch, 'title', '')),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        if (! empty($upsertRows)) {
            BookingWidgetBranchOrder::query()->upsert(
                $upsertRows,
                ['city_id', 'clinic_id', 'branch_id'],
                ['clinic_name', 'title', 'updated_at']
            );
        }

        $staleIds = BookingWidgetBranchOrder::query()
            ->where('city_id', $city->id)
            ->get(['id', 'clinic_id', 'branch_id'])
            ->reject(fn (BookingWidgetBranchOrder $branchOrder): bool => in_array(
                $this->makeBranchKey($branchOrder->clinic_id, $branchOrder->branch_id),
                $freshBranchKeys,
                true
            ))
            ->pluck('id')
            ->all();

        if (! empty($staleIds)) {
            BookingWidgetBranchOrder::query()
                ->whereIn('id', $staleIds)
                ->delete();
        }
    }

    private function shouldRefresh(int $cityId): bool
    {
        $latestUpdate = BookingWidgetBranchOrder::query()
            ->where('city_id', $cityId)
            ->max('updated_at');

        if (! $latestUpdate instanceof CarbonInterface && ! is_string($latestUpdate)) {
            return true;
        }

        return now()->subMinutes(self::REFRESH_TTL_MINUTES)->gte($latestUpdate);
    }

    private function resolveBookingCityId(City $city): ?int
    {
        $cities = $this->bookingWidgetApiService->extractList(
            $this->bookingWidgetApiService->getCities()
        );

        $normalizedLocalName = $this->normalizeCityName($city->name);

        $exactMatch = collect($cities)->first(function (array $bookingCity) use ($normalizedLocalName): bool {
            return $this->normalizeCityName((string) data_get($bookingCity, 'name')) === $normalizedLocalName;
        });

        if ($exactMatch) {
            return (int) data_get($exactMatch, 'id');
        }

        $partialMatch = collect($cities)->first(function (array $bookingCity) use ($normalizedLocalName): bool {
            $bookingName = $this->normalizeCityName((string) data_get($bookingCity, 'name'));

            return $bookingName !== '' && (
                str_contains($bookingName, $normalizedLocalName) ||
                str_contains($normalizedLocalName, $bookingName)
            );
        });

        return $partialMatch ? (int) data_get($partialMatch, 'id') : null;
    }

    private function normalizeCityName(?string $name): string
    {
        return mb_strtolower(trim((string) preg_replace('/^г\.\s*/ui', '', (string) $name)));
    }

    private function isClinicAllowed(int $clinicId): bool
    {
        $allowedClinicIds = collect(config('zrenie-clinic.booking_allowed_clinic_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($allowedClinicIds)) {
            return true;
        }

        return in_array($clinicId, $allowedClinicIds, true);
    }

    private function makeBranchKey(int $clinicId, int $branchId): string
    {
        return $clinicId . ':' . $branchId;
    }
}
