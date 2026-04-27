<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BalanceType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionIndexRequest extends FormRequest
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
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', Rule::enum(TransactionStatus::class)],
            'balance_type' => ['sometimes', Rule::enum(BalanceType::class)],
            'type' => ['sometimes', Rule::enum(TransactionType::class)],
        ];
    }
}
