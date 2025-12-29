<?php

namespace App\Http\Controllers\Api;

use App\Enums\PageType;
use App\Helpers\Categories;
use App\Http\Controllers\Controller;
use App\Http\Filters\PostFilter;
use App\Http\Requests\Api\Post\IndexRequest;
use App\Http\Resources\PostResource;
use App\Models\Doctor;
use App\Models\Page;
use App\Models\Tag;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ItemNotFoundException;


class PostController extends Controller
{
    public function __invoke(IndexRequest $request, $handle = '')
    {
        $path = $request->path();
        $cityService = app(\App\Services\CityService::class);
        $currentCity = $cityService->getCurrentCity();

        if ($currentCity && !$currentCity->is_default) {
            $prefix = $currentCity->slug . '/';
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
            }
        }

        $categories = Categories::getCategories();
        $categoryCurrent = null;

        if (!$handle) {
            $categoryCurrent = $categories->where('handle', $path)->first();

            if (!$categoryCurrent) {
                abort(404);
            }
        }

        $data = $request->validated();

        $count_items = 12 * (!isset($data['perpage']) ? 1 : $data['perpage']);

        $page = Page::query()
            ->where('type', PageType::Blog)
            ->where('active', true)
            ->first();

        if($handle){
            $tag = Tag::query()->where('handle', $handle)->firstOrFail();
            $posts = $tag->pages()->with(['tags', 'media'])->paginate($count_items);
            return view('posts.show')->with([
                'page' => $page,
                'title' => $tag->seo['title'] ?? $tag->title,
                'description' => $tag->seo['description'] ?? '',
                'filter' => null,
                'categories' => $categories,
                'category' => $tag,
                'posts' => $posts,
                'tag' => $tag,
            ]);
        }

        $filters = app()->make(PostFilter::class, ['queryParams' => array_filter($data)]);
        $posts = Page::filter($filters)->where('category_id', $categoryCurrent->id)->orderByDesc('created_at')->with(['tags', 'media', 'category'])->paginate($count_items);

        if(isset($data['perpage'])){
            return PostResource::collection($posts);
        }

        $filter = [
            'tags' => Cache::remember('posts_filter', 2592000, function() use ($posts) {
                return $posts->pluck('tags')->flatten()->unique('id')->pluck('title', 'id');
            }),
        ];

        return view('posts.show')->with([
            'page' => $page,
            'title' => $page->seo['title'] ?? $page->title,
            'description' => $page->seo['description'] ?? '',
            'filter' => $filter,
            'categories' => $categories,
            'category' => $categoryCurrent,
            'posts' => $posts,
            'tag' => null
        ]);
    }
}
