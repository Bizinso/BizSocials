# Local Development Setup & Execution Plan

## Document Information
- **Version**: 1.0.0
- **Created**: 2026-02-06
- **Purpose**: Complete local development environment and build plan

---

## 1. Development Phases

```
┌─────────────────────────────────────────────────────────────────┐
│                 DEVELOPMENT PHASES                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  PHASE 0: Environment Setup                                     │
│  ├── Docker & Docker Compose                                    │
│  ├── Database (MySQL 8.0)                                       │
│  ├── Redis (caching & queues)                                   │
│  ├── MinIO (S3-compatible storage)                              │
│  ├── MailHog (email testing)                                    │
│  └── Environment for Web + Mobile                               │
│                                                                 │
│  PHASE 1: Backend API (Laravel 11.x)                            │
│  ├── Project scaffolding                                        │
│  ├── Database migrations                                        │
│  ├── Authentication (Sanctum)                                   │
│  ├── Multi-tenancy implementation                               │
│  ├── Core API endpoints                                         │
│  ├── Unit & Feature tests                                       │
│  └── API documentation (OpenAPI/Swagger)                        │
│                                                                 │
│  PHASE 2: Web Application (Vue 3 + TypeScript)                  │
│  ├── Project scaffolding                                        │
│  ├── Component library                                          │
│  ├── State management (Pinia)                                   │
│  ├── Routing & authentication                                   │
│  ├── All UI modules                                             │
│  ├── E2E tests (Playwright)                                     │
│  └── Build optimization                                         │
│                                                                 │
│  PHASE 3: Mobile App (Flutter)                                  │
│  ├── Project scaffolding                                        │
│  ├── Shared API client                                          │
│  ├── Core features                                              │
│  ├── Platform-specific setup (iOS/Android)                      │
│  └── Testing                                                    │
│                                                                 │
│  PHASE 4: Integration & Testing                                 │
│  ├── End-to-end testing                                         │
│  ├── Performance testing                                        │
│  ├── Security testing                                           │
│  └── Documentation                                              │
│                                                                 │
│  PHASE 5: CI/CD & Deployment Readiness                          │
│  ├── Docker production images                                   │
│  ├── CI/CD pipeline configs                                     │
│  ├── Kubernetes/Docker Compose prod                             │
│  └── Monitoring setup                                           │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Project Structure

```
bizsocials/
├── docker/                      # Docker configurations
│   ├── development/
│   │   ├── docker-compose.yml
│   │   ├── mysql/
│   │   ├── redis/
│   │   ├── nginx/
│   │   └── minio/
│   └── production/
│       ├── docker-compose.yml
│       └── Dockerfile.*
│
├── backend/                     # Laravel API
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── tests/
│   ├── Dockerfile
│   └── composer.json
│
├── frontend/                    # Vue 3 Web App
│   ├── src/
│   ├── public/
│   ├── tests/
│   ├── Dockerfile
│   └── package.json
│
├── mobile/                      # Flutter App
│   ├── lib/
│   ├── ios/
│   ├── android/
│   ├── test/
│   └── pubspec.yaml
│
├── docs/                        # Documentation
│   ├── *.md
│   └── test-cases/
│
├── scripts/                     # Utility scripts
│   ├── setup.sh
│   ├── seed.sh
│   └── test.sh
│
├── .gitlab-ci.yml              # CI/CD config (GitLab ready)
├── .github/                    # GitHub Actions (alternative)
├── Makefile                    # Common commands
└── README.md
```

---

## 3. Technology Stack

### Backend
| Component | Technology | Version |
|-----------|------------|---------|
| Framework | Laravel | 11.x (11.48) |
| PHP | PHP | 8.3+ |
| Database | MySQL | 8.0 |
| Cache | Redis | 7.x |
| Queue | Laravel Horizon + Redis | - |
| Search | Laravel Scout + Meilisearch | - |
| File Storage | MinIO (S3-compatible) | - |
| API Docs | Scramble (OpenAPI) | - |

### Frontend (Web)
| Component | Technology | Version |
|-----------|------------|---------|
| Framework | Vue | 3.4+ |
| Language | TypeScript | 5.x |
| Build Tool | Vite | 5.x |
| State | Pinia | 2.x |
| Router | Vue Router | 4.x |
| UI Library | Tailwind CSS | 3.x |
| HTTP Client | Axios | 1.x |
| Testing | Vitest + Playwright | - |

### Mobile
| Component | Technology | Version |
|-----------|------------|---------|
| Framework | Flutter | 3.19+ |
| Language | Dart | 3.x |
| State | flutter_bloc | 8.x |
| Storage | Drift | 2.x |
| HTTP | Dio | 5.x |

### Infrastructure
| Component | Technology |
|-----------|------------|
| Containers | Docker + Docker Compose |
| Reverse Proxy | Nginx |
| CI/CD | GitLab CI (ready) |
| Monitoring | Laravel Telescope (dev) |

---

## 4. Environment Setup Commands

### Prerequisites Check
```bash
# Required software
docker --version          # Docker 24+
docker-compose --version  # Docker Compose 2+
php --version            # PHP 8.3+
composer --version       # Composer 2+
node --version           # Node 20+
npm --version            # NPM 10+
flutter --version        # Flutter 3.19+
```

### Quick Start
```bash
# Clone and setup
cd bizsocials

# Start all services
make up

# Or manually
docker-compose -f docker/development/docker-compose.yml up -d

# Backend setup
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Frontend setup
cd frontend
npm install
npm run dev

# Mobile setup
cd mobile
flutter pub get
```

---

## 5. Docker Compose (Development)

```yaml
# docker/development/docker-compose.yml
version: '3.8'

services:
  # MySQL Database
  mysql:
    image: mysql:8.0
    container_name: bizsocials_mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-rootpassword}
      MYSQL_DATABASE: ${DB_DATABASE:-bizsocials}
      MYSQL_USER: ${DB_USERNAME:-bizsocials}
      MYSQL_PASSWORD: ${DB_PASSWORD:-secret}
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./mysql/init:/docker-entrypoint-initdb.d
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

  # Redis
  redis:
    image: redis:7-alpine
    container_name: bizsocials_redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

  # MinIO (S3-compatible storage)
  minio:
    image: minio/minio:latest
    container_name: bizsocials_minio
    restart: unless-stopped
    environment:
      MINIO_ROOT_USER: ${MINIO_ACCESS_KEY:-minioadmin}
      MINIO_ROOT_PASSWORD: ${MINIO_SECRET_KEY:-minioadmin}
    ports:
      - "9000:9000"
      - "9001:9001"
    volumes:
      - minio_data:/data
    command: server /data --console-address ":9001"
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:9000/minio/health/live"]
      interval: 30s
      timeout: 20s
      retries: 3

  # MailHog (Email testing)
  mailhog:
    image: mailhog/mailhog:latest
    container_name: bizsocials_mailhog
    restart: unless-stopped
    ports:
      - "1025:1025"
      - "8025:8025"

  # Meilisearch (Full-text search)
  meilisearch:
    image: getmeili/meilisearch:latest
    container_name: bizsocials_meilisearch
    restart: unless-stopped
    environment:
      MEILI_MASTER_KEY: ${MEILISEARCH_KEY:-masterkey}
      MEILI_NO_ANALYTICS: true
    ports:
      - "7700:7700"
    volumes:
      - meilisearch_data:/meili_data

  # PHP-FPM (Laravel Backend)
  php:
    build:
      context: ../../backend
      dockerfile: Dockerfile.dev
    container_name: bizsocials_php
    restart: unless-stopped
    volumes:
      - ../../backend:/var/www/html
      - ./php/local.ini:/usr/local/etc/php/conf.d/local.ini
    depends_on:
      mysql:
        condition: service_healthy
      redis:
        condition: service_healthy
    environment:
      - DB_HOST=mysql
      - REDIS_HOST=redis
      - MAIL_HOST=mailhog
      - AWS_ENDPOINT=http://minio:9000

  # Nginx
  nginx:
    image: nginx:alpine
    container_name: bizsocials_nginx
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - ../../backend:/var/www/html
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - php

  # Laravel Horizon (Queue Worker)
  horizon:
    build:
      context: ../../backend
      dockerfile: Dockerfile.dev
    container_name: bizsocials_horizon
    restart: unless-stopped
    volumes:
      - ../../backend:/var/www/html
    command: php artisan horizon
    depends_on:
      - php
      - redis

  # Laravel Scheduler
  scheduler:
    build:
      context: ../../backend
      dockerfile: Dockerfile.dev
    container_name: bizsocials_scheduler
    restart: unless-stopped
    volumes:
      - ../../backend:/var/www/html
    command: sh -c "while true; do php artisan schedule:run --verbose; sleep 60; done"
    depends_on:
      - php

volumes:
  mysql_data:
  redis_data:
  minio_data:
  meilisearch_data:

networks:
  default:
    name: bizsocials_network
```

---

## 6. Makefile Commands

```makefile
# Makefile

.PHONY: help up down restart logs shell test migrate seed fresh

# Default target
help:
	@echo "BizSocials Development Commands"
	@echo "================================"
	@echo "make up        - Start all services"
	@echo "make down      - Stop all services"
	@echo "make restart   - Restart all services"
	@echo "make logs      - View logs"
	@echo "make shell     - Enter PHP container shell"
	@echo "make test      - Run all tests"
	@echo "make migrate   - Run database migrations"
	@echo "make seed      - Seed the database"
	@echo "make fresh     - Fresh migrate with seeds"

# Docker commands
up:
	docker-compose -f docker/development/docker-compose.yml up -d

down:
	docker-compose -f docker/development/docker-compose.yml down

restart:
	docker-compose -f docker/development/docker-compose.yml restart

logs:
	docker-compose -f docker/development/docker-compose.yml logs -f

shell:
	docker exec -it bizsocials_php bash

# Backend commands
migrate:
	docker exec bizsocials_php php artisan migrate

seed:
	docker exec bizsocials_php php artisan db:seed

fresh:
	docker exec bizsocials_php php artisan migrate:fresh --seed

test:
	docker exec bizsocials_php php artisan test

test-coverage:
	docker exec bizsocials_php php artisan test --coverage

# Frontend commands
frontend-install:
	cd frontend && npm install

frontend-dev:
	cd frontend && npm run dev

frontend-build:
	cd frontend && npm run build

frontend-test:
	cd frontend && npm run test

# Mobile commands
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

# Full setup
setup: up
	@echo "Waiting for services to be ready..."
	sleep 10
	docker exec bizsocials_php composer install
	docker exec bizsocials_php php artisan key:generate
	docker exec bizsocials_php php artisan migrate --seed
	cd frontend && npm install
	cd mobile && flutter pub get
	@echo "Setup complete!"

# Cleanup
clean:
	docker-compose -f docker/development/docker-compose.yml down -v
	rm -rf backend/vendor
	rm -rf frontend/node_modules
	rm -rf mobile/.dart_tool
```

---

## 7. Execution Order

### Step 1: Create Project Structure
```bash
# Create directories
mkdir -p docker/development/{mysql,nginx,php}
mkdir -p docker/production
mkdir -p backend
mkdir -p frontend
mkdir -p mobile
mkdir -p scripts
```

### Step 2: Setup Docker Environment
```bash
# Create docker-compose and configs
# Start services
make up
```

### Step 3: Create Laravel Backend
```bash
composer create-project laravel/laravel backend
cd backend
# Install packages, configure, create migrations
```

### Step 4: Create Vue Frontend
```bash
npm create vue@latest frontend
cd frontend
# Install packages, configure
```

### Step 5: Create Flutter Mobile
```bash
flutter create mobile
cd mobile
# Add dependencies, configure
```

### Step 6: Implement Features
- Follow the module specifications in docs/
- Write tests alongside code
- Document as you go

### Step 7: Integration Testing
- Test all APIs
- Test web application
- Test mobile application

### Step 8: CI/CD Setup
- Configure GitLab CI
- Build Docker production images
- Test deployment locally

---

## 8. Next Steps

1. **Create Docker environment** - Set up all services
2. **Scaffold Laravel backend** - Project structure
3. **Create database migrations** - All entities
4. **Implement authentication** - Multi-tenant auth
5. **Build core APIs** - Step by step
6. **Create Vue frontend** - Component by component
7. **Mobile app** - After web is complete

---

## 9. Success Criteria

| Milestone | Criteria |
|-----------|----------|
| Environment Ready | All Docker services running |
| Backend Ready | All APIs working, tests passing |
| Frontend Ready | All pages working, E2E tests passing |
| Mobile Ready | App running on iOS + Android |
| CI/CD Ready | Pipeline runs successfully |
| Deployment Ready | Docker images build and run |
