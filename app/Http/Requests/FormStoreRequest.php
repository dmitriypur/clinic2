<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class FormStoreRequest extends FormRequest
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
            "client_fio" => ['required', 'string'],
            "client_inn" => ['required', 'string'],
            "client_phone" => ['required', 'string'],
            "client_passnumber" => ['required', 'string'],
            "client_passdate" => ['required', 'date:d.m.Y'],
            "client_passwhom" => ['required', 'string'],
            "client_passkod" => ['required', 'string'],
            "client_dob" => ['required', 'string', 'date:d.m.Y'],
            "patient_fio" => ['required', 'string'],
            "patient_snils" => ['required', 'string'],
            "patient_type" => ['required', 'in:0,1'],
            "patient_number" => ['required', 'string'],
            "patient_date" => ['required', 'string', 'date:d.m.Y'],
            "patient_dob" => ['required', 'string', 'date:d.m.Y'],
            "patient_inn" => ['required', 'string'],
            "patient_kin" => ['required', 'string'],
            "email" => ['required', 'string', 'email'],
            "year" => ['required', 'string'],
            "privacy" => ['accepted'],
            "confirm" => ['accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'client_phone' => Str::of($this->client_phone)
                ->replaceMatches('/[^0-9]+/', '')
                ->value(),
        ]);
    }

}
