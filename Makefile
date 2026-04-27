.PHONY: build rebuild-php up down install migrate db-fresh logs shell test storage-link

build:
	docker compose build

# После смены composer.json на php ^8.4 пересоберите образы без кэша, иначе в контейнере останется старый PHP 8.3.
rebuild-php:
	docker compose build --no-cache app queue scheduler

up:
	docker compose up -d

down:
	docker compose down

install:
	docker compose run --rm --no-deps app composer install --no-interaction --prefer-dist
	docker compose run --rm --no-deps node sh -c "npm ci 2>/dev/null || npm install"

migrate:
	docker compose run --rm app php artisan migrate --force

# ОПАСНО: php artisan migrate:fresh — удаляет ВСЕ таблицы и данные (админ, Steam-пользователи, сделки).
# Именованный том postgres_data НЕ удаляется; пустая схема создаётся заново в том же томе.
# Для обычных миграций без потери данных используй только: make migrate
db-fresh:
	docker compose run --rm app php artisan migrate:fresh --force

logs:
	docker compose logs -f

shell:
	docker compose exec app sh

test:
	docker compose run --rm app php artisan test

storage-link:
	docker compose run --rm app php artisan storage:link

setup: build install migrate storage-link up
	@echo "Открой http://localhost:8080 — приложение. Vite: http://localhost:5173"
	@echo "Почта (Mailpit): http://localhost:8025 — все письма из app/queue/scheduler"
