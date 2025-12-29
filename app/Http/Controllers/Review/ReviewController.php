<?php

namespace App\Http\Controllers\Review;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\ReviewRequest;
use App\Models\Review;

class ReviewController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ReviewRequest $request)
    {
        Review::query()->create([
            'name' => $request->name,
            'body' => $request->body,
            'rating' => $request->rating,
        ]);
    }
}
