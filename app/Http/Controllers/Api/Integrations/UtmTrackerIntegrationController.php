<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;
use App\Services\UtmTrackerIntegrationExportService;
use Illuminate\Http\JsonResponse;

class UtmTrackerIntegrationController extends Controller
{
    public function __invoke(UtmTrackerIntegrationExportService $exportService): JsonResponse
    {
        return response()->json($exportService->export());
    }
}
