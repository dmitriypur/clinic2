<?php

namespace App\Http\Resources;

use App\Enums\ResourcesForReviews;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Review
 */
class ReviewResource extends JsonResource
{
    public static $wrap = 'review';

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'body_html' => $this->body_html,
            'rating' => $this->rating,
            'pages' => $this->resource->pages->pluck('title', 'handle'),
            'get_date' => Carbon::parse($this->get_date)->format('d.m.Y'),
            'link_resource' => $this->link_resource,
            'doctor' => str_replace('.', '', trim($this->doctorInitials)),
            'resource' => $this->resource->resource ?? null,
            'resources' => $this->resource->resource ? collect([ResourcesForReviews::icons()[$this->resource->resource], ResourcesForReviews::toArray()[$this->resource->resource]]) : null,
        ];
    }
}
