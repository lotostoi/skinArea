# ТЗ: синхронизация справочника скинов CS2

## Цель

Хранить в PostgreSQL **справочник предметов** (не лоты маркета): стабильный внешний ключ, отображаемое имя, URL изображения (CDN Steam), нормализованные `rarity` и `category` в терминах SkinsArena. Данные подтягиваются **ETL-командой** из настраиваемого **внешнего JSON** и обновляются по расписанию.

**Граница:** таблица `skin_catalog_items` **не заменяет** `market_items`. Лоты P2P по-прежнему создаются при выставлении из инвентаря. Справочник используется для:

- единообразных названий/иконок в UI;
- привязки лотов и призов кейсов (опциональное поле `skin_catalog_external_id`);
- будущих цен/фильтров (отдельные задачи).

## Источник данных (MVP)

По умолчанию: публичный JSON репозитория [ByMykel/CSGO-API](https://github.com/ByMykel/CSGO-API) (`public/api/en/skins.json`). Это не официальный Steam API; лицензия и актуальность — на стороне потребителя. При необходимости URL меняется через `SKIN_CATALOG_SOURCE_URL`.

Альтернативы (для будущих итераций ТЗ): Steam Web API `GetAssetClassInfo` (нужны `classid`), коммерческие каталоги, ручной CSV — **не смешивать** в одном прогоне без отдельного адаптера.

## Схема БД

Таблица `skin_catalog_items`:

| Колонка | Тип | Описание |
|---------|-----|----------|
| id | bigserial | PK |
| external_id | string, unique | ID из источника (например `skin-e757fd7191f9`) |
| name | string | Отображаемое имя |
| image_url | string nullable | Полный HTTPS URL |
| rarity | string nullable | Значение `ItemRarity` проекта |
| category | string nullable | Значение `ItemCategory` проекта |
| weapon_name | string nullable | Из блока `weapon.name` JSON |
| last_synced_at | timestamp nullable | Время последнего успешного upsert строки |
| created_at, updated_at | timestamps | |

Идемпотентность: `upsert` по `external_id`.

Опциональная связка:

- `market_items.skin_catalog_external_id` (nullable, string) — FK логический на `skin_catalog_items.external_id`
- `case_items.skin_catalog_external_id` (nullable, string)

## Команда и очередь

- `php artisan skins:catalog-sync` — синхронный прогон.
- Опции: `--dry-run` (без записи), `--limit=N` (для тестов), `--queue` (отправить `SyncSkinCatalogJob` в очередь).
- Таймаут HTTP и User-Agent — из `config/skinsarena.php`.

## Расписание (cron)

Laravel `Schedule::command('skins:catalog-sync')->dailyAt('04:15')` в `routes/console.php`. В Docker сервис `scheduler` выполняет `php artisan schedule:work`.

## Конфигурация

Все URL и таймауты — в `config/skinsarena.php`, ключи в `.env`:

- `SKIN_CATALOG_SOURCE_URL`
- `SKIN_CATALOG_HTTP_TIMEOUT` (секунды)

## Ошибки и лимиты

- При ошибке HTTP или невалидном JSON — логирование (`Log::error`), команда завершается с ненулевым кодом.
- Ретраи на уровне job (если используется `--queue`) — стандартные `tries`/`backoff` Laravel.
- Не выполнять агрессивный скрейп Steam Community Market без юридической оценки ToS.

## Админка (следующие итерации)

- Filament: список `SkinCatalogItem`, дата последнего sync, кнопка «Запустить синк» (вызов команды/job).
- Не входит в минимальную реализацию текущего ТЗ.

## Тесты

Feature-тест: `Http::fake` с укороченным JSON, `artisan skins:catalog-sync --limit=2`, проверка количества строк в БД.

## Связь с MVP

См. [05-mvp-roadmap.md](05-mvp-roadmap.md), раздел «Справочник предметов CS2». Витрина маркета по-прежнему наполняется лотами; для демо пустой витрины допускаются сидер или ручное создание лотов в Filament.
