<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawBalanceRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:'.config('skinsarena.balance.min_withdrawal')],
        ];
    }

    public function attributes(): array
    {
        return [
            'amount' => 'сумма',
        ];
    }
}
