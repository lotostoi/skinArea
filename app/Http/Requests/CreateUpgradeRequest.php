<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUpgradeRequest extends FormRequest
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
        $min = config('skinsarena.upgrade.min_bet');
        $max = config('skinsarena.upgrade.max_bet');

        return [
            'bet_amount' => ['required', 'numeric', "min:{$min}", "max:{$max}"],
            'target_item_name' => ['required', 'string', 'max:512'],
            'target_item_price' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function attributes(): array
    {
        return [
            'bet_amount' => 'ставка',
            'target_item_name' => 'целевой предмет',
            'target_item_price' => 'цена цели',
        ];
    }
}
