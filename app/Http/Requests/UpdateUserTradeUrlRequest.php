<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserTradeUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string|Closure>>
     */
    public function rules(): array
    {
        return [
            'trade_url' => [
                'required',
                'string',
                'max:1024',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        $fail('Укажите trade-ссылку Steam.');

                        return;
                    }
                    $url = trim($value);
                    if ($url === '') {
                        $fail('Укажите trade-ссылку Steam.');

                        return;
                    }
                    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                        $fail('Некорректная ссылка.');

                        return;
                    }
                    $host = parse_url($url, PHP_URL_HOST);
                    if (! is_string($host) || ! str_ends_with(strtolower($host), 'steamcommunity.com')) {
                        $fail('Ссылка должна быть с домена steamcommunity.com.');

                        return;
                    }
                    $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
                    $query = (string) (parse_url($url, PHP_URL_QUERY) ?? '');
                    $combined = strtolower($path.'?'.$query);
                    if (
                        ! str_contains($combined, 'tradeoffer/new')
                        || ! str_contains($url, 'partner=')
                        || ! str_contains($url, 'token=')
                    ) {
                        $fail('Нужна ссылка «Создать обмен» с параметрами partner и token (скопируйте на странице приватности Steam).');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'trade_url.required' => 'Укажите trade-ссылку Steam.',
            'trade_url.max' => 'Ссылка не длиннее 1024 символов.',
        ];
    }
}
