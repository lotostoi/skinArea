<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseCartRequest extends FormRequest
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
            'market_item_ids' => ['required', 'array', 'min:1'],
            'market_item_ids.*' => ['integer', 'exists:market_items,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'market_item_ids' => 'позиции корзины',
        ];
    }
}
