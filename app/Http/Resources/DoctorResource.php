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
            'receives_display' => $this->receives_display,
            'age_min_months' => $this->age_min_months,
            'age_max_months' => $this->age_max_months,
            'receives_text' => $this->receives_text,
        ];
    }
}
