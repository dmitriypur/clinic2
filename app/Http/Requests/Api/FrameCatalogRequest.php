<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\FrameGender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FrameCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ages' => ['sometimes', 'array', 'max:12'],
            'ages.*' => ['integer', 'distinct', Rule::exists('frame_age_groups', 'id')->where('is_active', true)],
            'genders' => ['sometimes', 'array', 'max:2'],
            'genders.*' => ['string', 'distinct', Rule::in(FrameGender::values())],
            'offset' => ['sometimes', 'integer', 'min:0', 'max:10000'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:12'],
        ];
    }
}
