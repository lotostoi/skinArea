<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:64'],
            'status' => ['required', 'string', 'in:succeeded,failed'],
            'reason' => ['nullable', 'string', 'max:255'],
            'payload' => ['nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'idempotency_key' => 'ключ идемпотентности',
            'status' => 'статус платежа',
            'reason' => 'причина ошибки',
            'payload' => 'данные платежа',
        ];
    }
}
