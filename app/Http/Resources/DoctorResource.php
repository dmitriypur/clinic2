<?php

namespace App\Http\Resources;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Doctor
 */
class DoctorResource extends JsonResource
{
    public static $wrap = 'doctor';

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'surname' => $this->surname,
            'bio' => $this->bio,
            'excerpt' => $this->excerpt,
            'job_title' => $this->job_title,
            'speciality' => $this->speciality,
            'video_url' => $this->actual_video_url,
            'avatar_image' => $this->avatar_image?->toHtml() ?? null,
        ];
    }
}
