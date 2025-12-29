<?php

namespace App\Http\Requests\Review;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $service
 * @property string $name
 * @property string $body
 * @property numeric $rating
 */
class ReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'service' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
            'rating' => ['required', 'int', 'max:5'],
        ];
    }
}
