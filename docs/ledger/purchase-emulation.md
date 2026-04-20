# Эмуляция покупки без Steam

Временное решение, пока не готов Steam-трейд. Воспроизводит логику ТЗ п.7.2 и 8.3: при нажатии «Оплатить» деньги покупателя мгновенно списываются и кладутся в удержание, а через настраиваемый срок (по умолчанию 7 дней) считаются завершёнными и уходят продавцу за вычетом комиссии.

## Поток

1. `POST /api/v1/cart/purchase` — [`CartPurchaseController::store`](../../app/Http/Controllers/Api/CartPurchaseController.php).
2. [`App\Actions\Market\PurchaseCart`](../../app/Actions/Market/PurchaseCart.php):
   - lock на `market_items` и баланс покупателя;
   - проверка `status = active` для каждой позиции, запрет покупать свой лот;
   - на каждую позицию: создаётся `Deal(status=paid, expires_at=now + N days)`, `LedgerService::hold(buyer, price, $deal)` (две posted-проводки: `-price@main`, `+price@hold`), `market_item.status → reserved`.
3. Через N дней фоновая команда [`deals:settle-due`](../../app/Console/Commands/DealsSettleDueCommand.php) вызывает [`App\Actions\Market\SettleDeal`](../../app/Actions/Market/SettleDeal.php):
   - `LedgerService::transferHoldToSeller($deal)` — `-price@hold(buyer)`, `+price-commission@main(seller)`, `+commission@main(platform)`;
   - `deal.status = completed`, `market_item.status = sold`.
4. Ручная отмена — [`App\Actions\Market\CancelDeal`](../../app/Actions/Market/CancelDeal.php) через `deals:cancel {id}`:
   - `LedgerService::release()` возвращает деньги покупателю;
   - `deal.status = cancelled`, `market_item.status = active`.

## Конфиг

```env
SKINSARENA_MARKET_COMMISSION_PERCENT=5.0   # % комиссии платформы
SKINSARENA_PURCHASE_HOLD_DAYS=7            # срок удержания (Steam-защита)
```

## Расписание

В [`routes/console.php`](../../routes/console.php) команда `deals:settle-due` запускается каждые 10 минут.

Для локальной отладки сделок с `expires_at` в прошлом можно дернуть вручную:

```bash
php artisan deals:settle-due
php artisan deals:cancel 42 --reason="тест"
```

## Что НЕ делает эмуляция

- Не создаёт Steam Trade Offer (нет кнопки «Передать» у продавца, нет Steam-проверки трейда).
- Не штрафует участников за просроченные трейды (ТЗ п.7.5 — будет позже).
- Не начисляет бонус 10% мгновенной выдачи (ТЗ п.8.4 — отдельная фича).
- Не обновляет UI в реальном времени (WebSocket уведомления — отдельно).

Когда появится Steam-сервис, эмуляцию заменит цепочка `Deal.status: paid → trade_sent → trade_accepted → completed`, а `transferHoldToSeller` останется на завершающем шаге без изменений.
