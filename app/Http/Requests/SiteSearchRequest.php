<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SiteSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            $this->inputKey() => ['nullable', 'string', 'max:100'],
        ];
    }

    public function term(): string
    {
        return (string) $this->validated($this->inputKey(), '');
    }

    protected function prepareForValidation(): void
    {
        $key = $this->inputKey();
        $value = $this->input($key);

        if (is_string($value)) {
            $this->merge([
                $key => trim((string) preg_replace('/\s+/u', ' ', $value)),
            ]);
        }
    }

    private function inputKey(): string
    {
        return $this->routeIs('live.search') ? 'query' : 'q';
    }
}
