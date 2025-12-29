<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $search = $request->input('q');
        $results = [];

        if (!empty($search)) {
            $results = Page::query()->where('title', 'like', "%{$search}%")
                ->where('active', 1)
                ->orWhere('body_html', 'like', "%{$search}%")
                ->orWhereHas('blocks', function($q) use ($search) {
                    $q->where('body_html', 'like', "%{$search}%")->orWhere('payload', 'like', "%{$search}%");
                })
                ->with(['blocks' => function($q) use ($search) {
                    $q->where('body_html', 'like', "%{$search}%")->orWhere('payload', 'like', "%{$search}%");
                }])
                ->select('handle', 'title')
                ->paginate(30);
        }

        return view('search.results', compact('results', 'search'));
    }

    public function liveSearch(Request $request)
    {
        $query = $request->get('query');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = Page::query()->where('title', 'like', "%{$query}%")
            ->where('active', 1)
            ->orWhere('body_html', 'like', "%{$query}%")
            ->orWhereHas('blocks', function($q) use ($query) {
                $q->where('body_html', 'like', "%{$query}%")->orWhere('payload', 'like', "%{$query}%");
            })
            ->with(['blocks' => function($q) use ($query) {
                $q->where('body_html', 'like', "%{$query}%")->orWhere('payload', 'like', "%{$query}%");
            }])
            ->select('handle', 'title')
            ->limit(5)
            ->get();

        return response()->json($results);
    }
}
