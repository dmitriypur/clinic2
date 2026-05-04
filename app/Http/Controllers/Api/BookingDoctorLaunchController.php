<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Services\BookingDoctorLaunchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingDoctorLaunchController extends Controller
{
    public function __construct(
        private readonly BookingDoctorLaunchService $bookingDoctorLaunchService,
    ) {
    }

    public function __invoke(Request $request, string $doctor): JsonResponse
    {
        $validated = $request->validate([
            'site_city_id' => ['required', 'integer'],
            'booking_city_id' => ['required', 'integer'],
            'birth_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $siteCity = City::query()
            ->where('active', true)
            ->findOrFail((int) $validated['site_city_id']);

        $payload = $this->bookingDoctorLaunchService->getDoctorForLaunch(
            uuid: $doctor,
            bookingCityId: (int) $validated['booking_city_id'],
            birthDate: $validated['birth_date'] ?? null,
            siteCity: $siteCity,
        );

        abort_if($payload === null, 404);

        return response()->json([
            'data' => $payload,
        ]);
    }
}
