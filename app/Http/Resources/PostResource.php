<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'title' => $this->title,
            'handle' => $this->getUrl(),
            'body_html' => str(reduction($this->body_html, 80))->sanitizeHtml(),
            'tags' => $this->tags->pluck('title', 'handle'),
            'created_at' => $this->created_at->format('d.m.Y'),
            'image' => $this->getImageUrl('default') ?? null,
        ];
    }
}
