<?php

namespace App\Http\Controllers;

use App\Http\Requests\SiteSearchRequest;
use App\Services\CityService;
use App\Services\SiteSearchAnalyticsRecorder;
use App\Services\SiteSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function search(
        SiteSearchRequest $request,
        SiteSearchService $searchService,
        SiteSearchAnalyticsRecorder $analyticsRecorder,
        CityService $cityService,
    ): View
    {
        $search = $request->term();
        $results = $searchService->search($search, page: (int) $request->integer('page', 1));

        try {
            $analyticsRecorder->record($search, $cityService->getCurrentCity()?->id, $results->total());
        } catch (\Throwable $exception) {
            report($exception);
        }

        return view('search.results', compact('results', 'search'));
    }

    public function liveSearch(SiteSearchRequest $request, SiteSearchService $searchService): JsonResponse
    {
        $results = $searchService->suggest($request->term())
            ->map(fn ($result) => [
                ...$result->toArray(),
                'handle' => $result->url,
            ]);

        return response()->json($results);
    }
}
