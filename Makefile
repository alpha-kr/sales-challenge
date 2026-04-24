.PHONY: setup dev build test down check-docker

check-docker:
	@command -v docker >/dev/null 2>&1 || { echo "❌ Docker is not installed. See https://docs.docker.com/get-docker/"; exit 1; }
	@docker info >/dev/null 2>&1 || { echo "❌ Docker is not running. Please start Docker Desktop or the Docker daemon."; exit 1; }
	@docker compose version >/dev/null 2>&1 || { echo "❌ Docker Compose plugin not found. See https://docs.docker.com/compose/install/"; exit 1; }
	@echo "✅ Docker and Docker Compose are ready."

setup: check-docker
	@echo "→ Copying .env files..."
	@cp -n laravel-backend/.env.example laravel-backend/.env 2>/dev/null && echo "  Created laravel-backend/.env" || echo "  laravel-backend/.env already exists"
	@echo "→ Starting containers (waiting for healthy)..."
	WWWUSER=$$(id -u) WWWGROUP=$$(id -g) docker compose --env-file laravel-backend/.env up -d --wait
	@echo "→ Installing PHP dependencies & running migrations..."
	docker compose --env-file laravel-backend/.env exec sales.challenge bash -c "cd /var/www/html && composer install --no-interaction && php artisan key:generate --ansi && php artisan migrate --force --seed"
	@echo "→ Installing frontend dependencies..."
	docker compose --env-file laravel-backend/.env exec sales.challenge bash -c "cd /var/www/frontend && npm install"
	@echo ""
	@echo "✅ Setup complete. Run 'make dev' to start developing."

dev: check-docker
	@echo "→ Starting containers..."
	WWWUSER=$$(id -u) WWWGROUP=$$(id -g) docker compose --env-file laravel-backend/.env up -d
	@echo "→ Starting Vite dev server (HMR on http://localhost:5173)..."
	docker compose --env-file laravel-backend/.env exec sales.challenge bash -c "cd /var/www/frontend && npm run dev"

build:
	@echo "→ Building frontend for production..."
	WWWUSER=$$(id -u) WWWGROUP=$$(id -g) docker compose up -d
	docker compose exec sales.challenge bash -c "cd /var/www/frontend && npm run build"

test:
	docker compose exec sales.challenge bash -c "cd /var/www/html && php artisan test --compact"

down:
	docker compose down
