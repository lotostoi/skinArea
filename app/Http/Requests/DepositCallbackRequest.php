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
            'payload' => ['required', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'payload' => 'данные платежа',
        ];
    }
}
