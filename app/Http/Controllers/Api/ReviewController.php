<?php

namespace App\Http\Controllers\Api;

use App\Enums\PageType;
use App\Enums\ResourcesForReviews;
use App\Http\Controllers\Controller;
use App\Http\Filters\ReviewFilter;
use App\Http\Requests\Api\Review\IndexRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Doctor;
use App\Models\Page;
use App\Models\Review;

class ReviewController extends Controller
{
    public function __invoke(IndexRequest $request)
    {
        $data = $request->validated();
        $count_items = 12 * (!isset($data['perpage']) ? 1 : $data['perpage']);

        $filters = app()->make(ReviewFilter::class, ['queryParams' => array_filter($data)]);
        $reviews = Review::filter($filters)->with(['doctor', 'pages'])->orderByDesc('get_date')->paginate($count_items);

        if(isset($data['perpage'])){
            return ReviewResource::collection($reviews);
        }

        $page = Page::query()
            ->where('handle', 'reviews')
            ->where('active', true)
            ->with(['blocks.media'])
            ->first();

        $filter = [
            'resources' => [ResourcesForReviews::icons(), ResourcesForReviews::toArray()],
            'doctors' => Doctor::with('media')->get(),
            'services' => Page::query()->whereNot('handle', '=', 'services')->where('type', '=', PageType::Services)->where('active', '=', 1)->orderBy('sorting')->pluck('title', 'id'),
        ];

        return view('pages.show')->with([
            'page' => $page,
            'reviews' => $reviews,
            'filter' => $filter,
        ]);

    }
}
