<?php

declare(strict_types=1);

namespace App\Contracts;

interface SkinPriceProviderInterface
{
    /**
     * Возвращает цены в USD для переданных market_hash_name.
     * Если цена недоступна — значение null.
     *
     * @param  list<string>  $marketHashNames
     * @return array<string, float|null> ключ = market_hash_name, значение = цена USD
     */
    public function getPrices(array $marketHashNames): array;
}
