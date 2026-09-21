<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FrameCatalogRequest;
use App\Services\FrameCatalogService;
use Illuminate\Http\JsonResponse;

class FrameCatalogController extends Controller
{
    public function __invoke(FrameCatalogRequest $request, FrameCatalogService $catalog): JsonResponse
    {
        $data = $request->validated();
        $page = $catalog->page(
            ages: $data['ages'] ?? [],
            genders: $data['genders'] ?? [],
            offset: (int) ($data['offset'] ?? 0),
            limit: (int) ($data['limit'] ?? FrameCatalogService::INITIAL_LIMIT),
        );

        return response()->json([
            'html' => $catalog->renderCards($page['frames']),
            'total' => $page['total'],
            'hasMore' => $page['hasMore'],
            'nextOffset' => $page['nextOffset'],
        ]);
    }
}
