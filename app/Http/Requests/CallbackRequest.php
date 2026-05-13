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
    private const TYPE_CALLBACK_NEW = 'Заявка на звонок';

    private const TYPE_CALLBACK_LEGACY = 'callback_form';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('type') === self::TYPE_CALLBACK_LEGACY) {
            $this->merge([
                'type' => self::TYPE_CALLBACK_NEW,
            ]);
        }
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
            'type' => ['nullable', 'string', 'in:' . self::TYPE_CALLBACK_NEW],
            'privacy' => ['nullable', 'accepted'],
        ];
    }
}
