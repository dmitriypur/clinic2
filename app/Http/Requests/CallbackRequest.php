<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $name
 * @property string $phone
 * @property ?string $city
 * @property ?string $utm_source
 * @property ?string $utm_medium
 * @property ?string $utm_campaign
 * @property ?string $utm_content
 * @property ?string $utm_term
 * @property ?string $source
 * @property ?string $type
 */
class CallbackRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'phone:RU'],
            'city' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'in:site,vk_mini_app'],
            'type' => ['nullable', 'string', 'in:callback_form'],
            'privacy' => ['nullable', 'accepted'],
        ];
    }
}
