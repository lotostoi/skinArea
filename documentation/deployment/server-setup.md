# Деплой SkinsArena: что сделано в репозитории и что сделать тебе

Этот файл — единая инструкция. Файлы **`docker-compose.prod.yml`** и **`.github/workflows/ci-deploy.yml`** уже лежат в корне репозитория; раздел «Файлы для копирования» внизу оставлен как дубликат на случай восстановления.

---

## Что добавляется в проект (со стороны разработки)

| Файл | Назначение |
|------|------------|
| `docker-compose.prod.yml` | Прод: nginx **публично :8080** (`http://IP:8080`). Для Caddy на 80/443 — в compose заменить на `127.0.0.1:8080:80`, без Vite, Postgres/Redis без проброса наружу. **Mailpit:** SMTP только внутри Docker (`mailpit:1025`), веб-UI по пути **`/mailpit/`** на том же домене, что и сайт (прокси в `docker/nginx/default.conf`, порты Mailpit наружу не пробрасываются) |
| `.github/workflows/ci-deploy.yml` | PR/push в `main` или `master`: тесты; после **push** в эту ветку — SSH на VPS и скрипт деплоя |

Локальная разработка по-прежнему: **`docker compose`** + `docker-compose.yml` (порт **8080**, Vite **5173**) — **не заменяется**.

---

## Секреты GitHub (уже частично есть)

В **Settings → Secrets and variables → Actions** должны быть:

| Имя | Значение |
|-----|----------|
| `SSH_PRIVATE_KEY` | Полный текст приватного SSH-ключа (файл **без** `.pub`) |
| `SSH_HOST` | IP или домен VPS |
| `SSH_USER` | Логин Linux на сервере (тот же, что в `authorized_keys`) |
| `DEPLOY_PATH` | **Абсолютный путь** к каталогу с клоном репозитория на сервере, например `/var/www/skinsarena` |

Без **`DEPLOY_PATH`** workflow не узнает, куда `cd` на сервере.

Перед `git reset` деплой выполняет **`sudo chown -R $(whoami):$(whoami) .`**, чтобы снять владение **`www-data`** с `storage`/`bootstrap/cache` после Docker и не ловить `unable to unlink … Permission denied`. Пользователь из **`SSH_USER`** должен иметь **passwordless sudo** для `chown` по этому каталогу (или глобально для деплоя), иначе шаг упадёт.

### Filament (`/admin`): почему «неверный логин» и что делает деплой

- **`php artisan migrate --force` не очищает таблицы** и не «генерит данные заново» — только накатывает новые миграции. Пользователи и прочие строки остаются, пока ты сам не делаешь `migrate:fresh` / `db:wipe` / `docker compose down -v` с удалением тома Postgres.
- Вход в админку — это **email + пароль из таблицы `users`**, а не «любой» адрес. Если в БД до сих пор `admin@skinsarena.local` из примера, а ты вводишь `admin@mail.ru`, получишь ту же ошибку, что и при неверном пароле.
- На сервере в **`.env`** задай **`ADMIN_EMAIL`**, **`ADMIN_PASSWORD`**, **`ADMIN_STEAM_ID`** (см. `config/skinsarena.php`). После деплоя CI выполняет **`php artisan db:seed --class=AdminUserSeeder --force`**: для строки с этим `steam_id` подтягиваются email и пароль из `.env` (идемпотентно). **Пароль, изменённый только в интерфейсе Filament без правки `.env`, при следующем деплое снова заменится значением из `.env`.**

---

## Что сделать на VPS (один раз)

1. **Docker + Compose** — по официальным гайдам (см. ранее: docs.docker.com).
2. **Git** — `sudo apt install -y git`.
3. **Пользователь деплоя** (не обязательно root): SSH-ключ в `~/.ssh/authorized_keys`.
4. **Клон репозитория** (публичный репо):
   ```bash
   sudo mkdir -p /var/www && sudo chown "$USER":"$USER" /var/www
   cd /var/www
   git clone https://github.com/ВАШ_ЛОГИН/SkinsArena.git skinsarena
   cd skinsarena
   ```
   Путь `/var/www/skinsarena` = значение **`DEPLOY_PATH`** в секретах.

5. **Приватный репозиторий** — на сервере нужен доступ к GitHub:
   - либо [Deploy key](https://docs.github.com/en/authentication/connecting-to-github-with-ssh/managing-deploy-keys) (read-only) в настройках репо,
   - либо `git clone` по SSH `git@github.com:...` с ключом на сервере.

6. **Файл `.env`** в каталоге клона (не в git):
   ```bash
   cp .env.example .env
   nano .env
   ```
   Обязательно для прода:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_KEY=` — сгенерировать один раз (локально или в контейнере): `php artisan key:generate`
   - `APP_URL=https://ваш-домен` (или `http://IP:8080`, пока нет домена/Caddy)
   - `DB_*` совпадают с тем, что ожидает `docker-compose.prod.yml` (`DB_PASSWORD` задаёт пароль Postgres в контейнере)
   - `FRONTEND_URL`, `CORS_ALLOWED_ORIGINS` — под твой домен/схему SPA
   - `STEAM_*`, `ADMIN_*` — по необходимости
   - **Почта (Mailpit):** в `.env` должны быть `MAIL_MAILER=smtp`, `MAIL_HOST=mailpit`, `MAIL_PORT=1025` (как в `.env.example`). Иначе после `php artisan config:cache` приложение не увидит SMTP-хост из compose. Веб-интерфейс писем: **`https://ваш-домен/mailpit/`** (или `http://IP:8080/mailpit/`, пока нет HTTPS). Весь трафик идёт через nginx на **8080** / через Caddy на 443 — отдельный порт Mailpit на проде не нужен.

7. **Первый запуск вручную** (проверка до CI):
   ```bash
   cd /var/www/skinsarena   # твой DEPLOY_PATH
   docker compose -f docker-compose.prod.yml run --rm --no-deps app composer install --no-dev --no-interaction --optimize-autoloader
   docker run --rm -v "$(pwd)":/var/www/html -w /var/www/html node:22-alpine sh -c "npm ci && npm run build"
   docker compose -f docker-compose.prod.yml up -d --build
   docker compose -f docker-compose.prod.yml exec -T app chown -R www-data:www-data storage bootstrap/cache
   docker compose -f docker-compose.prod.yml exec -u www-data -T app php artisan migrate --force
   docker compose -f docker-compose.prod.yml exec -u www-data -T app php artisan db:seed --class=AdminUserSeeder
   docker compose -f docker-compose.prod.yml exec -T app php artisan storage:link || true
   docker compose -f docker-compose.prod.yml exec -u www-data -T app php artisan config:cache
   docker compose -f docker-compose.prod.yml exec -u www-data -T app php artisan route:cache
   docker compose -f docker-compose.prod.yml exec -u www-data -T app php artisan view:cache
   ```

   Если Docker у тебя только через **`sudo`**, добавь **`sudo`** перед каждым `docker compose` и `docker run` в блоке выше. Готовый фрагмент — поднять стек и пересобрать кеши:

   ```bash
   sudo docker compose -f docker-compose.prod.yml up -d --build
   sudo docker compose -f docker-compose.prod.yml exec -T app chown -R www-data:www-data storage bootstrap/cache
   sudo docker compose -f docker-compose.prod.yml exec -u www-data -T app php artisan config:cache
   sudo docker compose -f docker-compose.prod.yml exec -u www-data -T app php artisan route:cache
   sudo docker compose -f docker-compose.prod.yml exec -u www-data -T app php artisan view:cache
   ```

   Проверка: снаружи `http://IP:8080` (открой порт в UFW: `sudo ufw allow 8080/tcp`). С сервера: `curl -I http://127.0.0.1:8080`.

8. **HTTPS и домен:** когда подключишь Caddy на **80/443**, в `docker-compose.prod.yml` у nginx поставь **`127.0.0.1:8080:80`**, в `Caddyfile`: `reverse_proxy 127.0.0.1:8080`, закрой публичный **8080** в UFW. Сайт снаружи: `https://ваш-домен`. Mailpit откроется по **`https://ваш-домен/mailpit/`** на том же `reverse_proxy` (путь не выносить на отдельный upstream — иначе nginx не сможет проксировать в контейнер `mailpit`).

**Composer на самой Ubuntu не обязателен** — зависимости ставятся **внутри контейнера** `app`.

**Docker Hub 429 / `sudo docker`:** `docker login` под пользователем пишет `~/.docker/config.json`, а **`sudo docker`** ходит в Hub **от root** и смотрит `/root/.docker/` — логина там нет → снова анонимный лимит. Либо **`sudo docker login`**, либо `sudo usermod -aG docker ВАШ_USER`, перелогин в SSH и **`docker compose` без sudo**. Образ `composer:2` в Dockerfile не используется — Composer ставится установщиком с getcomposer.org, остаётся тянуть в основном `php:8.4-fpm-bookworm`.

**HTTP 500, `tempnam(): file created in the system's temporary directory` (PHP 8.4):** PHP-FPM в контейнере — **`www-data`**. Пишет в `storage/framework/views` (Blade) и `bootstrap/cache`. Если каталоги принадлежат **root** или пользователю деплоя (после `sudo chown $(whoami)` на весь проект), а контейнер уже поднялся — любой запрос (в т.ч. **`/auth/steam-complete`** после Steam) может упасть с этой ошибкой. **Сразу на сервере:**  
`mkdir -p storage/framework/views storage/framework/cache storage/logs bootstrap/cache`  
`sudo chown -R www-data:www-data storage bootstrap/cache`  
`docker compose -f docker-compose.prod.yml exec -u www-data -T app php artisan view:clear` (при необходимости) и снова `exec -u www-data -T app php artisan config:cache` / `route:cache` / `view:cache`. В **GitHub Actions** деплой сначала выставляет `www-data` на `storage` и `bootstrap/cache` **до** `docker compose up`, затем повторяет `chown` после старта — см. `.github/workflows/ci-deploy.yml`.

**HTTP 502 Bad Gateway (страница от nginx):** для запросов к **`/`** и обычным страницам Laravel это не Mailpit — nginx не достучался до **PHP-FPM** (`app:9000`). В логах nginx часто: `connect() failed (111: Connection refused) ... upstream: "fastcgi://172.18.x.x:9000"` — контейнер **`nginx` не перезапускали**, а **`app` пересоздали** (деплой, `up --build`): у `app` сменился IP в сети Docker, nginx держит старый. **Сразу:** `docker compose -f docker-compose.prod.yml restart nginx`. В репозитории: `docker/nginx/default.conf` уже с **resolver + переменной** `fastcgi_pass`, чтобы IP обновлялся без ручного рестарта. Также проверь `docker compose ... logs app` (падение PHP, `.env`, БД, Redis, OOM).

---

## Что происходит при push в `main` или `master`

1. GitHub Actions: `composer install`, `npm ci`, `npm run build`, `php artisan test`.
2. Если тесты зелёные — SSH на VPS, в `DEPLOY_PATH`: `git fetch` и `git reset --hard`, `composer install --no-dev`, сборка фронта через `node`, **`mkdir` + `chown www-data` на `storage` и `bootstrap/cache` до `up`**, затем `docker compose -f docker-compose.prod.yml up -d --build`, `restart nginx`, повторный `chown`, миграции и кеши artisan от `www-data` (см. `.github/workflows/ci-deploy.yml`).

---

## Файлы для копирования в репозиторий

### `docker-compose.prod.yml` (корень репозитория)

Создай файл с содержимым ниже.

```yaml
services:
  nginx:
    image: nginx:1.27-alpine
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html:ro
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on:
      - app
    restart: unless-stopped
    networks:
      - skinsarena

  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    volumes:
      - .:/var/www/html
    env_file:
      - .env
    environment:
      DB_HOST: postgres
      REDIS_HOST: redis
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_started
    restart: unless-stopped
    networks:
      - skinsarena

  queue:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    user: www-data
    command: php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
    volumes:
      - .:/var/www/html
    env_file:
      - .env
    environment:
      DB_HOST: postgres
      REDIS_HOST: redis
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_started
      app:
        condition: service_started
    restart: unless-stopped
    networks:
      - skinsarena

  scheduler:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    user: www-data
    command: php artisan schedule:work
    volumes:
      - .:/var/www/html
    env_file:
      - .env
    environment:
      DB_HOST: postgres
      REDIS_HOST: redis
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_started
      app:
        condition: service_started
    restart: unless-stopped
    networks:
      - skinsarena

  postgres:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: ${DB_DATABASE:-skinsarena}
      POSTGRES_USER: ${DB_USERNAME:-skinsarena}
      POSTGRES_PASSWORD: ${DB_PASSWORD:-secret}
    volumes:
      - postgres_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME:-skinsarena} -d ${DB_DATABASE:-skinsarena}"]
      interval: 5s
      timeout: 5s
      retries: 10
    restart: unless-stopped
    networks:
      - skinsarena

  redis:
    image: redis:7-alpine
    volumes:
      - redis_data:/data
    restart: unless-stopped
    networks:
      - skinsarena

networks:
  skinsarena:
    driver: bridge

volumes:
  postgres_data:
  redis_data:
```

### `.github/workflows/ci-deploy.yml`

Создай каталог `.github/workflows/` и файл:

```yaml
name: CI and deploy

on:
  push:
    branches: [main, master]
  pull_request:
    branches: [main, master]

concurrency:
  group: ${{ github.workflow }}-${{ github.ref }}
  cancel-in-progress: true

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: "8.4"
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, pgsql, redis, intl, bcmath
          coverage: none

      - uses: actions/setup-node@v4
        with:
          node-version: "22"
          cache: npm

      - name: Install PHP dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader

      - name: Install JS dependencies and build
        run: |
          npm ci
          npm run build

      - name: Run tests
        run: php artisan test

  deploy:
    needs: test
    if: (github.ref == 'refs/heads/main' || github.ref == 'refs/heads/master') && github.event_name == 'push'
    runs-on: ubuntu-latest
    steps:
      - uses: appleboy/ssh-action@v1.2.0
        with:
          host: ${{ secrets.SSH_HOST }}
          username: ${{ secrets.SSH_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script_stop: true
          script: |
            set -euo pipefail
            cd "${{ secrets.DEPLOY_PATH }}"
            sudo chown -R "$(whoami):$(whoami)" .
            BRANCH="${{ github.ref_name }}"
            git fetch origin "$BRANCH"
            git reset --hard "origin/$BRANCH"
            docker compose -f docker-compose.prod.yml run --rm --no-deps app composer install --no-dev --no-interaction --optimize-autoloader
            docker run --rm -v "$(pwd)":/var/www/html -w /var/www/html node:22-alpine sh -c "npm ci && npm run build"
            docker compose -f docker-compose.prod.yml up -d --build
            docker compose -f docker-compose.prod.yml exec -T app chown -R www-data:www-data storage bootstrap/cache
            docker compose -f docker-compose.prod.yml exec -u www-data -T app php artisan migrate --force
            docker compose -f docker-compose.prod.yml exec -u www-data -T app php artisan db:seed --class=AdminUserSeeder --force
            docker compose -f docker-compose.prod.yml exec -T app php artisan storage:link || true
            docker compose -f docker-compose.prod.yml exec -u www-data -T app php artisan config:cache
            docker compose -f docker-compose.prod.yml exec -u www-data -T app php artisan route:cache
            docker compose -f docker-compose.prod.yml exec -u www-data -T app php artisan view:cache
```

---

## Чеклист после добавления файлов

- [ ] Закоммитить и запушить в GitHub ветку **`main`** или **`master`** (workflow смотрит на обе).
- [ ] Секрет **`DEPLOY_PATH`** совпадает с реальным путём к клону на сервере.
- [ ] На сервере в этом каталоге есть **`.env`** и уже был успешный ручной `up` (см. выше).
- [ ] У пользователя SSH на сервере есть право выполнять `docker` (группа `docker`) и писать в каталог клона.

Если нужно, чтобы файлы появились в репозитории без ручного копирования — включи **режим Agent** в Cursor и попроси: «добавь `docker-compose.prod.yml` и `.github/workflows/ci-deploy.yml` из documentation/deployment/server-setup.md».
