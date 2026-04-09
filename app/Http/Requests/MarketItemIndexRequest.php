<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ItemCategory;
use App\Enums\ItemWear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarketItemIndexRequest extends FormRequest
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
            'category' => ['sometimes', 'nullable', 'string', Rule::enum(ItemCategory::class)],
            'wear' => ['sometimes', 'nullable', 'string', Rule::enum(ItemWear::class)],
            'price_min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_max' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'per_page' => 'количество на странице',
            'price_min' => 'цена от',
            'price_max' => 'цена до',
            'search' => 'поиск',
        ];
    }
}
