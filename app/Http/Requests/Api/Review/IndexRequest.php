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
            'doctors' => ['nullable', 'array'],
            'doctors.*' => ['integer'],
            'resources' => ['nullable', 'array'],
            'resources.*' => ['integer'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer'],
            'perpage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
