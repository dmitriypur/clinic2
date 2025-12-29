<?php

namespace App\Http\Requests\Api\Review;
use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctors' => 'nullable|array',
            'resources' => 'nullable|array',
            'services' => 'nullable|array',
            'perpage' => 'nullable|numeric',
        ];
    }

}
