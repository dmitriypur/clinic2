<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $doctorId
 * @property string $date
 * @property string $time
 * @property string $firstName
 * @property string $lastName
 * @property ?string $middleName
 * @property string $birthdate
 * @property string $phone
 * @property ?string $utm_source
 * @property ?string $utm_medium
 * @property ?string $utm_campaign
 */
class MakingAnAppointmentRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'doctorId' => ['required', 'uuid'],
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'middleName' => ['required', 'string', 'max:255'],
            'birthdate' => ['required', 'date'],
            'phone' => ['required', 'string', 'phone:RU'],
            'privacy' => ['nullable', 'accepted'],
        ];
    }
}
