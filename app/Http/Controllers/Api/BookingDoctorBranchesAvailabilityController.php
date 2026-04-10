<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Services\BookingBranchEnrichmentService;
use App\Services\BookingWidgetApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingDoctorBranchesAvailabilityController extends Controller
{
    public function __construct(
        private readonly BookingWidgetApiService $bookingWidgetApiService,
        private readonly BookingBranchEnrichmentService $bookingBranchEnrichmentService,
    ) {
    }

    public function __invoke(Request $request, int $doctor): JsonResponse
    {
        $validated = $request->validate([
            'site_city_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'clinic_id' => ['nullable', 'integer'],
            'city_id' => ['nullable', 'integer'],
        ]);

        $siteCity = City::query()
            ->where('active', true)
            ->findOrFail((int) $validated['site_city_id']);

        $payload = $this->bookingWidgetApiService->getDoctorBranchesAvailability(
            $doctor,
            $validated['date'],
            isset($validated['clinic_id']) ? (int) $validated['clinic_id'] : null,
            isset($validated['city_id']) ? (int) $validated['city_id'] : null,
        );

        return response()->json(
            $this->bookingBranchEnrichmentService->enrichPayload($payload, $siteCity)
        );
    }
}
