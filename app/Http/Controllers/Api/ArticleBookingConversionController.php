<?php

namespace App\Http\Controllers\Api;

use App\Enums\PageType;
use App\Http\Controllers\Controller;
use App\Models\ArticleBookingConversion;
use App\Models\Page;
use App\Services\CityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ArticleBookingConversionController extends Controller
{
    public function __invoke(Request $request, CityService $cityService): JsonResponse
    {
        $data = $request->validate([
            'page_id' => ['required', 'integer'],
            'city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')],
            'event_uuid' => ['required', 'uuid'],
            'page_url' => ['required', 'string', 'max:2048'],
            'page_path' => ['required', 'string', 'max:1024'],
            'entry_point' => ['nullable', 'string', 'max:64'],
            'booking_mode' => ['nullable', 'string', 'max:32'],
        ]);

        $page = Page::withoutGlobalScopes()
            ->whereKey($data['page_id'])
            ->where('type', PageType::Posts)
            ->where('active', true)
            ->first();

        if (! $page) {
            throw ValidationException::withMessages([
                'page_id' => ['Счетчик можно записывать только для активной статьи.'],
            ]);
        }

        $cityId = $data['city_id'] ?? $cityService->getCurrentCity()?->id;

        $conversion = ArticleBookingConversion::query()->firstOrCreate(
            ['event_uuid' => $data['event_uuid']],
            [
                'page_id' => $page->id,
                'city_id' => $cityId,
                'page_url' => $data['page_url'],
                'page_path' => $data['page_path'],
                'entry_point' => $data['entry_point'] ?? 'booking_widget',
                'booking_mode' => $data['booking_mode'] ?? null,
            ],
        );

        return response()->json([
            'count' => article_booking_count($page, $cityId),
        ], $conversion->wasRecentlyCreated ? 201 : 200);
    }
}
