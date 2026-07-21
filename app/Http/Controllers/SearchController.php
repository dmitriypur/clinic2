<?php

namespace App\Http\Controllers;

use App\Http\Requests\SiteSearchRequest;
use App\Services\SiteSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function search(SiteSearchRequest $request, SiteSearchService $searchService): View
    {
        $search = $request->term();
        $results = $searchService->search($search, page: (int) $request->integer('page', 1));

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
