# ===========================================
# BizSocials - Development Commands
# ===========================================

.PHONY: help up down restart logs shell test migrate seed fresh build clean \
        frontend-install frontend-dev frontend-build frontend-test \
        mobile-get mobile-run mobile-test setup horizon-status

# Colors
YELLOW := \033[1;33m
GREEN := \033[1;32m
CYAN := \033[1;36m
NC := \033[0m

# Default target
help:
	@echo ""
	@echo "$(CYAN)BizSocials Development Commands$(NC)"
	@echo "================================="
	@echo ""
	@echo "$(YELLOW)Docker Commands:$(NC)"
	@echo "  make up          - Start all services"
	@echo "  make down        - Stop all services"
	@echo "  make restart     - Restart all services"
	@echo "  make logs        - View all logs"
	@echo "  make logs-app    - View app logs only"
	@echo "  make build       - Build Docker images"
	@echo "  make clean       - Stop and remove volumes"
	@echo ""
	@echo "$(YELLOW)Backend Commands:$(NC)"
	@echo "  make shell       - Enter PHP container shell"
	@echo "  make artisan     - Run artisan command (usage: make artisan cmd='migrate')"
	@echo "  make composer    - Run composer command (usage: make composer cmd='install')"
	@echo "  make migrate     - Run database migrations"
	@echo "  make seed        - Seed the database"
	@echo "  make fresh       - Fresh migrate with seeds"
	@echo "  make test        - Run PHPUnit tests"
	@echo "  make test-cov    - Run tests with coverage"
	@echo "  make horizon     - Start Horizon manually"
	@echo ""
	@echo "$(YELLOW)Frontend Commands:$(NC)"
	@echo "  make frontend-install  - Install npm dependencies"
	@echo "  make frontend-dev      - Start frontend dev server"
	@echo "  make frontend-build    - Build frontend for production"
	@echo "  make frontend-test     - Run frontend tests"
	@echo ""
	@echo "$(YELLOW)Mobile Commands:$(NC)"
	@echo "  make mobile-get        - Get Flutter dependencies"
	@echo "  make mobile-run        - Run Flutter app"
	@echo "  make mobile-test       - Run Flutter tests"
	@echo ""
	@echo "$(YELLOW)Setup Commands:$(NC)"
	@echo "  make setup       - Full initial setup"
	@echo "  make setup-env   - Copy .env.example to .env"
	@echo ""

# ===========================================
# Docker Commands
# ===========================================

up:
	@echo "$(GREEN)Starting all services...$(NC)"
	docker compose up -d
	@echo "$(GREEN)Services started!$(NC)"
	@echo ""
	@echo "$(CYAN)Available services:$(NC)"
	@echo "  - API:         http://localhost:8080"
	@echo "  - MailHog:     http://localhost:8025"
	@echo "  - MinIO:       http://localhost:9001"
	@echo "  - Meilisearch: http://localhost:7700"
	@echo ""

down:
	@echo "$(YELLOW)Stopping all services...$(NC)"
	docker compose down

restart:
	@echo "$(YELLOW)Restarting all services...$(NC)"
	docker compose restart

logs:
	docker compose logs -f

logs-app:
	docker compose logs -f app

build:
	@echo "$(GREEN)Building Docker images...$(NC)"
	docker compose build --no-cache

clean:
	@echo "$(YELLOW)Stopping services and removing volumes...$(NC)"
	docker compose down -v
	@echo "$(GREEN)Cleanup complete!$(NC)"

# ===========================================
# Backend Commands
# ===========================================

shell:
	docker compose exec app bash

artisan:
	docker compose exec app php artisan $(cmd)

composer:
	docker compose exec app composer $(cmd)

migrate:
	@echo "$(GREEN)Running migrations...$(NC)"
	docker compose exec app php artisan migrate

seed:
	@echo "$(GREEN)Seeding database...$(NC)"
	docker compose exec app php artisan db:seed

fresh:
	@echo "$(YELLOW)Fresh migrate with seeds...$(NC)"
	docker compose exec app php artisan migrate:fresh --seed

test:
	@echo "$(GREEN)Running tests...$(NC)"
	docker compose exec app php artisan test

test-cov:
	@echo "$(GREEN)Running tests with coverage...$(NC)"
	docker compose exec app php artisan test --coverage

horizon:
	docker compose exec app php artisan horizon

horizon-status:
	docker compose exec app php artisan horizon:status

tinker:
	docker compose exec app php artisan tinker

# ===========================================
# Frontend Commands
# ===========================================

frontend-install:
	cd frontend && npm install

frontend-dev:
	cd frontend && npm run dev

frontend-build:
	cd frontend && npm run build

frontend-test:
	cd frontend && npm run test

frontend-lint:
	cd frontend && npm run lint

# ===========================================
# Mobile Commands
# ===========================================

mobile-get:
	cd mobile && flutter pub get

mobile-run:
	cd mobile && flutter run

mobile-test:
	cd mobile && flutter test

mobile-build-android:
	cd mobile && flutter build apk

mobile-build-ios:
	cd mobile && flutter build ios

# ===========================================
# Setup Commands
# ===========================================

setup-env:
	@if [ ! -f backend/.env ]; then \
		cp backend/.env.example backend/.env; \
		echo "$(GREEN).env file created$(NC)"; \
	else \
		echo "$(YELLOW).env file already exists$(NC)"; \
	fi

setup: up
	@echo "$(GREEN)Setting up BizSocials...$(NC)"
	@echo ""
	@echo "$(CYAN)Waiting for services to be healthy...$(NC)"
	@sleep 15
	@echo ""
	@echo "$(CYAN)Installing Composer dependencies...$(NC)"
	docker compose exec -T app composer install
	@echo ""
	@echo "$(CYAN)Generating application key...$(NC)"
	docker compose exec -T app php artisan key:generate
	@echo ""
	@echo "$(CYAN)Running migrations...$(NC)"
	docker compose exec -T app php artisan migrate --force
	@echo ""
	@echo "$(CYAN)Seeding database...$(NC)"
	docker compose exec -T app php artisan db:seed --force
	@echo ""
	@echo "$(CYAN)Creating storage link...$(NC)"
	docker compose exec -T app php artisan storage:link
	@echo ""
	@echo "$(GREEN)======================================$(NC)"
	@echo "$(GREEN)Setup complete!$(NC)"
	@echo "$(GREEN)======================================$(NC)"
	@echo ""
	@echo "$(CYAN)Available services:$(NC)"
	@echo "  - API:         http://localhost:8080"
	@echo "  - MailHog:     http://localhost:8025"
	@echo "  - MinIO:       http://localhost:9001"
	@echo "  - Meilisearch: http://localhost:7700"
	@echo ""

# ===========================================
# Utility Commands
# ===========================================

ps:
	docker compose ps

mysql:
	docker compose exec mysql mysql -u bizsocials -pbizsocials bizsocials

redis-cli:
	docker compose exec redis redis-cli

cache-clear:
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan view:clear

optimize:
	docker compose exec app php artisan optimize

# ===========================================
# Code Quality
# ===========================================

lint:
	docker compose exec app ./vendor/bin/pint

lint-check:
	docker compose exec app ./vendor/bin/pint --test

analyze:
	docker compose exec app ./vendor/bin/phpstan analyse
