<?php

namespace App\Http\Requests\Profile;

use App\Models\User;
use App\Services\PhoneService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class ProfileUpdateRequest extends FormRequest
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
            'last_name' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date:d.m.Y'],
            'phone' => ['required', 'string', 'phone:RU'],
            'email' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {

        return [
            function (Validator $validator) {
                $user = User::where('phone', resolve(PhoneService::class)->make($this->phone))->first();
                
                if ($user && $user->id !== Auth::id()) {
                    $validator->errors()->add(
                        'phone',
                        'Такой телефон уже зарегистрирован'
                    );
                }
            }
        ];
    }

}
