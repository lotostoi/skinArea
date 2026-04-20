# Переход старых транзакций на журнал

## Что изменилось

До перехода таблица `transactions` не имела статуса и `balance_type`: считалось, что каждая запись — уже проведённая операция на `main`-балансе (или подразумевала hold через поле `metadata.action`). `balance_after` писался параллельно с изменением баланса в одной `DB::transaction`.

После перехода:

- Есть статусы `pending / posted / reversed / failed / cancelled` (поле `status`).
- Есть явный счёт `main | hold` (поле `balance_type`).
- Источник правды — `transactions.posted`. `balances` — кеш, пересчитываемый из журнала.
- Есть ключ идемпотентности `idempotency_key` для защиты от повторов (вебхуки, ретраи).

## Как мигрировались существующие данные

Миграция `2026_04_20_130000_add_journal_fields_to_transactions_table.php` добавляет новые колонки и backfill:

- Всем существующим строкам: `status = 'posted'`, `balance_type = 'main'`, `posted_at = created_at`.
- Индексы: `(user_id, balance_type, status, created_at)`, `(status, created_at)`, `(reverses_transaction_id)`, уникальный на `idempotency_key`.

Это корректный backfill, потому что до перехода все записи были фактически «проведённые движения на main». Инвариант после миграции сразу выполняется — проверяется командой `ledger:check`.

## Запуск

```
php artisan migrate
php artisan ledger:check          # должен сообщить, что инвариант сохранён
php artisan ledger:rebuild-balances --dry-run  # для проверки без записи
php artisan ledger:rebuild-balances            # пересчитать кеш при расхождениях
```

## Сидеры

`DatabaseSeeder` вызывает:

1. `AdminUserSeeder` — админ.
2. `PlatformUserSeeder` — системный пользователь `platform` для комиссий маркета/кейсов.

ID платформенного пользователя опционально можно закрепить в `.env`:

```
PLATFORM_USER_ID=2
```

Если не задан, LedgerService резолвит пользователя по `PLATFORM_STEAM_ID` (по умолчанию `76561198000000002`).

## Что ещё НЕ переведено

Потоки, которые возвращают 501 и ждут реализации, но уже получат правильный движок:

- `CartPurchaseController` (покупка на маркете) — использует `LedgerService::hold()` + `transferHoldToSeller()`.
- `DealController::send/cancel` — `release()` или `transferHoldToSeller()` + переход `Deal.status`.
- `CaseController::open` — `createPending(case_open)` → `post()` в одной транзакции с выдачей `CaseOpening`.
- `UpgradeController::store` — аналогично кейсам.
- `BalanceController::withdraw` — сейчас заявка заводится в Filament; при переходе на флоу пользователя: `createPending(withdrawal)` при создании заявки, `post()` при её завершении.

Везде ключ `idempotency_key` обязателен, если операция может быть повторена фронтом или вебхуком.

## Полезные ссылки

- Модель учёта: [docs/ledger/accounting-model.md](accounting-model.md)
- Сервис: `app/Services/LedgerService.php`
- Миграция: `database/migrations/2026_04_20_130000_add_journal_fields_to_transactions_table.php`
