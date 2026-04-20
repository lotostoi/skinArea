# Модель учёта (ledger)

## Идея

Таблица `transactions` — **единый журнал** (ledger) всех денежных движений, аналог банковской главной книги.
Таблица `balances` — **кеш** быстрого чтения. Значение любого баланса всегда должно совпадать с суммой posted-строк журнала по той же паре `(user_id, balance_type)`.

## Инвариант

Для каждой пары `(user_id, balance_type)`:

```sql
balances.amount
  = SUM(transactions.amount)
    WHERE transactions.status = 'posted'
```

Нарушение инварианта — бага. Проверяется командой `php artisan ledger:check`.
Восстанавливается командой `php artisan ledger:rebuild-balances` (пересчёт кеша из журнала).

## Статусы транзакций

```
pending  → posted    (успешное проведение)
pending  → failed    (ошибка платёжной системы, безопасная отмена)
pending  → cancelled (отменено пользователем/админом до проведения)
posted   → reversed  (откат уже проведённой операции; создаётся обратная posted-строка)
```

Посттерминальные статусы (`failed`, `cancelled`, `reversed`) не меняют кеш `balances` сами по себе.
Откат (`reverse`) изменяет кеш симметрично создаваемой компенсирующей proводкой.

## Поля `transactions`

| Поле | Описание |
| --- | --- |
| `id` | Первичный ключ. |
| `user_id` | Пользователь (включая системного `platform`). |
| `type` | Тип операции (`deposit`, `withdrawal`, `purchase`, `sale`, `case_open`, `case_sell`, `upgrade`). Справочник не меняется. |
| `status` | `pending` / `posted` / `reversed` / `failed` / `cancelled`. |
| `balance_type` | `main` / `hold` — на какой счёт пишется эта сторона проводки. |
| `amount` | Сумма со знаком. Минус = списание, плюс = зачисление. |
| `balance_after` | Кеш остатка по балансу **после** проведения. Пишется только в `post()` и в компенсирующих posted-проводках. |
| `reference_type` / `reference_id` | Полиморфная ссылка на связанный объект (Deal, WithdrawalRequest и т.п.). |
| `metadata` | Свободный JSON: provider, leg, commission, failure_reason, reverse_reason. |
| `posted_at` | Момент перехода в `posted`. |
| `reversed_at` | Момент перехода из `posted` в `reversed`. |
| `reverses_transaction_id` | ID исходной транзакции для компенсирующей строки. |
| `idempotency_key` | Уникальный ключ (до 64 символов); защита от двойной записи при ретраях и вебхуках. |

## Примеры проводок

### Пополнение (deposit, 1000 ₽, fake-провайдер)

На нажатии «Оплатить» создаётся `pending`. После успешного колбэка (или сразу в случае fake) она переводится в `posted`.

```
INSERT transactions (type=deposit, status=pending, balance_type=main, amount=+1000, idempotency_key=fake-deposit-xxx)
-- ... платёжка ответила succeeded
UPDATE transactions SET status=posted, posted_at=now(), balance_after=<new>
UPDATE balances SET amount = amount + 1000 WHERE user_id=... AND type=main
```

### Вывод (withdrawal, 500 ₽)

Админ завершает `WithdrawalRequest`. Создаётся `pending` и сразу `posted`:

```
INSERT transactions (type=withdrawal, status=pending, balance_type=main, amount=-500,
                     reference=WithdrawalRequest#42, idempotency_key=withdrawal-42)
UPDATE transactions SET status=posted, posted_at=now(), balance_after=<new>
UPDATE balances SET amount = amount - 500 WHERE user_id=... AND type=main
```

### Hold (покупка на маркете, этап 1 — удержание)

Движение `main → hold` — это **две** `posted`-строки одной сделки:

```
-- Списание с main
INSERT transactions (type=purchase, status=posted, balance_type=main, amount=-1000,
                     reference=Deal#77, metadata.leg=main)
-- Зачисление в hold
INSERT transactions (type=purchase, status=posted, balance_type=hold, amount=+1000,
                     reference=Deal#77, metadata.leg=hold)
UPDATE balances main  → -1000
UPDATE balances hold  → +1000
```

### Settle (покупка на маркете, этап 2 — перевод продавцу)

Три `posted`-строки:

```
INSERT transactions (type=purchase, status=posted, balance_type=hold,  amount=-1000, user=buyer,   reference=Deal#77, metadata.leg=buyer_hold)
INSERT transactions (type=sale,     status=posted, balance_type=main,  amount=+950,  user=seller,  reference=Deal#77, metadata.leg=seller, metadata.commission=50)
INSERT transactions (type=sale,     status=posted, balance_type=main,  amount=+50,   user=platform,reference=Deal#77, metadata.leg=platform_commission)
```

### Reverse

Ошибка после `post()` (например, трейд не ушёл в Steam, а деньги уже перевели):

```
UPDATE transactions #A SET status=reversed, reversed_at=now(), metadata.reverse_reason=...
INSERT transactions #B (status=posted, amount=-amount(#A), balance_type=A.balance_type,
                        reverses_transaction_id=A.id, metadata.reverse_reason=...)
UPDATE balances  — на -amount(A)
```

Инвариант сохраняется: `A` перестаёт учитываться в `SUM(posted)`, `B` добавляет обратную сумму.

## Платформенный пользователь

Комиссии маркета, кейсов и апгрейда учитываются как зачисления системному пользователю `platform`.
Его id определяется в этом порядке:

1. `config('skinsarena.platform.user_id')` (`.env: PLATFORM_USER_ID`).
2. Иначе — по `config('skinsarena.platform.steam_id')` (сидится `PlatformUserSeeder`).

Если ни то, ни другое не настроено, комиссия не проводится (только остаётся в `metadata`).

## API (`GET /api/v1/transactions`)

Доступны query-фильтры (все опциональные):

- `status` — одно из `pending|posted|reversed|failed|cancelled`.
- `balance_type` — `main` или `hold`.
- `type` — один из значений `TransactionType`.

## Где что живёт

- Enum: `app/Enums/TransactionStatus.php`, `app/Enums/BalanceType.php`, `app/Enums/TransactionType.php`.
- Сервис: `app/Services/LedgerService.php` (+ DTO в `app/Services/Ledger/Dto/`).
- Миграция: `database/migrations/2026_04_20_130000_add_journal_fields_to_transactions_table.php`.
- Команды: `app/Console/Commands/LedgerRebuildBalancesCommand.php`, `app/Console/Commands/LedgerCheckCommand.php`.
- API: `app/Http/Controllers/Api/TransactionController.php`, `app/Http/Resources/TransactionResource.php`.
- Filament: `app/Filament/Resources/Transactions/Tables/TransactionsTable.php` — фильтры и действие «Откатить».
