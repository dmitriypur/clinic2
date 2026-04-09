<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BookingSiteDoctorsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingDoctorsController extends Controller
{
    public function __construct(
        private readonly BookingSiteDoctorsService $bookingSiteDoctorsService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $raw = (string) $request->query('uuids', '');
        $payload = $this->bookingSiteDoctorsService->getPayloadByUuids(explode(',', $raw));

        return response()->json([
            'data' => $payload['data'] ?? [],
            'meta' => [
                'hidden_uuids' => $payload['meta']['hidden_uuids'] ?? [],
            ],
        ]);
    }
}
