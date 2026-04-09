<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Services\BookingDoctorsByDateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingCityDoctorsByDateCalendarController extends Controller
{
    public function __construct(
        private readonly BookingDoctorsByDateService $bookingDoctorsByDateService,
    ) {
    }

    public function __invoke(Request $request, int $city): JsonResponse
    {
        $validated = $request->validate([
            'site_city_id' => ['required', 'integer'],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d'],
            'birth_date' => ['nullable', 'date_format:Y-m-d'],
            'clinic_id' => ['nullable', 'integer'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $siteCity = City::query()
            ->where('active', true)
            ->findOrFail((int) $validated['site_city_id']);

        return response()->json(
            $this->bookingDoctorsByDateService->getCalendarAvailability(
                bookingCityId: $city,
                dateFrom: $validated['date_from'],
                dateTo: $validated['date_to'],
                birthDate: $validated['birth_date'] ?? null,
                siteCity: $siteCity,
                clinicId: isset($validated['clinic_id']) ? (int) $validated['clinic_id'] : null,
                branchId: isset($validated['branch_id']) ? (int) $validated['branch_id'] : null,
            )
        );
    }
}
