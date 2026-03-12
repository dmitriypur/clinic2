<?php

namespace App\Http\Requests\Api\Integrations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ServiceApplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dry_run' => ['nullable', 'boolean'],
            'operations' => ['required', 'array', 'min:1'],
            'operations.*.type' => ['required', 'string', 'in:create_service,update_service,delete_service,upsert_price,delete_price'],
            'operations.*.ref' => ['nullable', 'string', 'max:100'],
            'operations.*.service_uuid' => ['nullable', 'uuid'],
            'operations.*.service_ref' => ['nullable', 'string', 'max:100'],
            'operations.*.parent_uuid' => ['nullable', 'uuid'],
            'operations.*.parent_ref' => ['nullable', 'string', 'max:100'],
            'operations.*.title' => ['nullable', 'string', 'max:255'],
            'operations.*.sort_order' => ['nullable', 'integer'],
            'operations.*.is_active' => ['nullable', 'boolean'],
            'operations.*.city_slugs' => ['nullable', 'array'],
            'operations.*.city_slugs.*' => ['string', 'exists:cities,slug'],
            'operations.*.city_slug' => ['nullable', 'string', 'exists:cities,slug'],
            'operations.*.price' => ['nullable', 'integer', 'min:0'],
            'operations.*.old_price' => ['nullable', 'integer', 'min:0'],
            'operations.*.price_from' => ['nullable', 'boolean'],
            'operations.*.cascade_children' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('operations', []) as $index => $operation) {
                $path = "operations.{$index}";
                $type = $operation['type'] ?? null;

                if ($type === 'create_service') {
                    if (! filled($operation['title'] ?? null)) {
                        $validator->errors()->add("{$path}.title", 'Для операции create_service обязательно поле title.');
                    }
                }

                if (in_array($type, ['update_service', 'delete_service', 'upsert_price', 'delete_price'], true)) {
                    $hasUuid = filled($operation['service_uuid'] ?? null);
                    $hasRef = filled($operation['service_ref'] ?? null);

                    if (! $hasUuid && ! $hasRef) {
                        $validator->errors()->add("{$path}.service_uuid", 'Нужно передать service_uuid или service_ref.');
                    }
                }

                if ($type === 'upsert_price') {
                    if (! array_key_exists('price', $operation)) {
                        $validator->errors()->add("{$path}.price", 'Для операции upsert_price обязательно поле price.');
                    }
                }
            }
        });
    }
}
