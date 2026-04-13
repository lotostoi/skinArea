# SkinsArena

P2P-маркетплейс скинов CS2, кейсы, апгрейд. **Laravel 13 + Vue + PostgreSQL + Redis**, Docker.

## Запуск через Docker

Требования: Docker Desktop / Docker Engine + Compose v2.

```bash
make build      # собрать образ PHP
make install    # composer + npm (без поднятия всего стека)
make migrate    # миграции PostgreSQL
make up         # nginx :8080, postgres, redis, mailpit, queue, scheduler, node (Vite :5173)
```

Или одной командой (после первого `make build`):

```bash
make setup
```

- **Приложение (основной вход в SPA + API):** http://localhost:8080 — открывайте **именно этот адрес**, иначе запросы к `/api/v1` пойдут не туда.  
- **Админ-панель (Filament):** http://localhost:8080/admin — вход по **email и паролю** учётки с ролью `admin` (создаётся сидером, см. ниже).  
- **Vite (HMR):** http://localhost:5173 — для горячей перезагрузки; в Docker настроен **proxy** на nginx (`BACKEND_URL`), API с 5173 тоже работает. Без Docker при `npm run dev` по умолчанию прокси на `127.0.0.1:8080`.  
- **PostgreSQL:** localhost:5432 (user `skinsarena`, db `skinsarena`, password `secret`)  
- **Redis:** localhost:6379  
- **Mailpit (перехват почты):** веб-интерфейс `http://localhost:8080/mailpit/` (через nginx; опционально напрямую `http://localhost:8025/mailpit/`). SMTP для Laravel внутри Docker: `mailpit:1025` (см. `.env.example`: `MAIL_HOST=mailpit`). С хоста без Docker для теста: `MAIL_HOST=127.0.0.1`, порт `1025`.

Остановка: `make down`

После правок `.env` в Docker выполните: `docker compose exec app php artisan config:clear` (или перезапустите контейнер `app`), иначе PHP может держать старый конфиг.

### Почему пропали пользователь / админ / данные

Я (или Cursor) **не стираем** базу сами. Обычно виновато одно из двух:

1. **`docker compose down -v`** — флаг **`-v`** удаляет именованные тома (в т.ч. **`postgres_data`**). После следующего `up` PostgreSQL поднимается **пустым**. Без `-v` данные в томе **сохраняются**. Локальный Redis в `docker-compose.yml` без тома (кэш/очередь не персистятся между пересозданиями контейнера).
2. **`make db-fresh`** (раньше в Makefile называлось `fresh`) — внутри выполняется **`php artisan migrate:fresh`**: дропаются **все таблицы**, миграции накатываются заново. **Том не удаляется**, но **все строки в БД исчезают** (включая админа из сидера). После этого снова нужны миграции + сидер админа.

**Безопасно для данных:** `make migrate` (только новые миграции), `make down` **без** `-v`, перезагрузка ПК/Docker Desktop.

После пустой БД: `docker compose exec app php artisan migrate --force` и `docker compose exec app php artisan db:seed --class=AdminUserSeeder` (или полный `db:seed`).

### Ошибка «Composer … require PHP >= 8.4», а в контейнере 8.3

Зависимости (Filament 5) требуют **PHP 8.4**. Образ в `docker/php/Dockerfile` уже на 8.4; если видите 8.3.30, контейнер собран со старого слоя кэша. Пересоберите PHP-сервисы без кэша и перезапустите стек:

```bash
make rebuild-php
docker compose up -d
```

Проверка: `docker compose exec app php -v` → должно быть **8.4.x**. Локальный `php artisan` без Docker тоже должен быть 8.4+.

## Переменные окружения (авторизация и SPA)

Скопируйте `.env.example` в `.env` и задайте как минимум:

| Переменная | Назначение |
|------------|------------|
| `APP_URL` | Базовый URL бэкенда (для Docker: `http://localhost:8080`). Используется в callback Steam, если не задан `STEAM_REDIRECT_URI`. |
| `STEAM_API_KEY` | Ключ Web API Steam ([Steam](https://steamcommunity.com/dev/apikey)); для Socialite Steam-провайдера задаётся как `client_secret`. |
| `STEAM_REDIRECT_URI` | Опционально: полный URL callback (должен совпасть с настройкой OpenID в Steam). По умолчанию: `{APP_URL}/auth/steam/callback`. |
| `FRONTEND_URL` | URL SPA (по умолчанию `http://localhost:5173`). После Steam пользователь перенаправляется на `{FRONTEND_URL}/auth/steam-complete?code=...`. |
| `CORS_ALLOWED_ORIGINS` | Запятая через перечисление origin’ов SPA для credentialed CORS (например `http://localhost:5173,http://127.0.0.1:5173`). |
| `SANCTUM_STATEFUL_DOMAINS` | При cookie-сессии для API — домены фронта; для **Bearer token** после обмена кода можно не трогать. |

Первый администратор (email/пароль для Filament, плюс фиктивный `steam_id` для уникальности):

| Переменная | Назначение |
|------------|------------|
| `ADMIN_EMAIL` | Email входа в `/admin` (по умолчанию `admin@skinsarena.local`). |
| `ADMIN_PASSWORD` | Пароль (в проде обязательно сменить). |
| `ADMIN_STEAM_ID` | SteamID64, уникальный среди пользователей (по умолчанию тестовый). |

После `make migrate` (или `php artisan migrate`) выполните `php artisan db:seed` — создаётся/обновляется админ из `ADMIN_*`. Обычные пользователи после входа через Steam получают роль `user`; роль `admin` только вручную/через сидер.

### Поток Steam → SPA (Sanctum token)

1. Браузер открывает `GET {APP_URL}/auth/steam` (редирект в Steam OpenID).  
2. Callback `GET /auth/steam/callback` создаёт/обновляет пользователя, кладёт одноразовый `code` в кеш и редиректит на `{FRONTEND_URL}/auth/steam-complete?code=...`.  
3. SPA вызывает `POST /api/v1/auth/steam/exchange` с телом `{ "code": "..." }` и получает `{ "data": { "token": "...", "token_type": "Bearer", "user": ... } }`. Дальнейшие запросы к API: заголовок `Authorization: Bearer <token>`.  
4. `POST /auth/logout` (web) или отзыв токена через Sanctum — по выбранной на фронте схеме.

В ТЗ MVP иногда упоминается JWT; в проекте для API используется **Laravel Sanctum** (личный access token для SPA эквивалентен по задаче).

## Сервисы в `docker-compose.yml`

| Сервис | Роль |
|--------|------|
| nginx | HTTP → `public/`, PHP-FPM |
| app | php-fpm 8.4 (pgsql, redis, pcntl; требование Filament 5) |
| postgres | PostgreSQL 16 |
| redis | кеш, сессии, очереди |
| queue | `php artisan queue:work redis` |
| scheduler | `php artisan schedule:work` |
| node | Vite dev |

## Документация

См. каталог `documentation/` — ТЗ, MVP-план, глоссарий.
