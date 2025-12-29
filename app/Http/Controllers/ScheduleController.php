<?php

namespace App\Http\Controllers;

use App\Contracts\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function __construct(protected ScheduleService $scheduleService)
    {
        //
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(['doctors' => $this->scheduleService->get()]);
    }
}
