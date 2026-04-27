<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CaseOpeningStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CaseInventoryIndexRequest extends FormRequest
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
            'status' => ['sometimes', Rule::enum(CaseOpeningStatus::class)],
            'sort' => ['sometimes', Rule::in(['created_at', 'won_item_price'])],
            'order' => ['sometimes', Rule::in(['asc', 'desc'])],
            'search' => ['sometimes', 'string', 'max:255'],
            'case_id' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
