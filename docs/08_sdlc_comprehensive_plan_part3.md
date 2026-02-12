# BizSocials — SDLC Plan (Part 3)

*Continuation of 08_sdlc_comprehensive_plan.md*

---

## 11. CI/CD Pipeline

### 11.1 Pipeline Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           CI/CD PIPELINE                                     │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  TRIGGER: Push to branch / PR opened                                        │
│                                                                             │
│  ┌─────────┐   ┌─────────┐   ┌─────────┐   ┌─────────┐   ┌─────────┐       │
│  │  Lint   │──►│  Test   │──►│  Build  │──►│Security │──►│ Deploy  │       │
│  │  Check  │   │  Suite  │   │  Stage  │   │  Scan   │   │  Stage  │       │
│  └─────────┘   └─────────┘   └─────────┘   └─────────┘   └─────────┘       │
│       │             │             │             │             │             │
│       ▼             ▼             ▼             ▼             ▼             │
│   - ESLint      - PHPUnit     - Docker      - SAST        - Dev (auto)     │
│   - PHP CS      - Vitest      - npm build   - Secrets     - Staging (auto) │
│   - Prettier    - E2E         - Assets      - Dependencies- Prod (manual)  │
│                 - Coverage                                                  │
│                                                                             │
│  GATES:                                                                     │
│  ✓ All linting passes                                                       │
│  ✓ Tests pass with 80%+ coverage                                            │
│  ✓ No high/critical security issues                                         │
│  ✓ Docker image builds successfully                                         │
│  ✓ Approval for production deployment                                       │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 11.2 GitHub Actions - Backend Pipeline

```yaml
# .github/workflows/backend-ci.yml
name: Backend CI

on:
  push:
    branches: [main, develop]
    paths:
      - 'backend/**'
      - '.github/workflows/backend-ci.yml'
  pull_request:
    branches: [main, develop]
    paths:
      - 'backend/**'

env:
  PHP_VERSION: '8.3'
  MYSQL_DATABASE: bizsocials_test
  MYSQL_ROOT_PASSWORD: secret

jobs:
  lint:
    name: Code Style
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: backend

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          tools: composer, php-cs-fixer

      - name: Cache Composer dependencies
        uses: actions/cache@v3
        with:
          path: backend/vendor
          key: ${{ runner.os }}-composer-${{ hashFiles('backend/composer.lock') }}

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run PHP CS Fixer
        run: vendor/bin/php-cs-fixer fix --dry-run --diff

      - name: Run PHPStan
        run: vendor/bin/phpstan analyse

  test:
    name: Tests
    runs-on: ubuntu-latest
    needs: lint
    defaults:
      run:
        working-directory: backend

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: ${{ env.MYSQL_ROOT_PASSWORD }}
          MYSQL_DATABASE: ${{ env.MYSQL_DATABASE }}
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

      redis:
        image: redis:7
        ports:
          - 6379:6379

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          extensions: pdo_mysql, redis
          coverage: xdebug

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Copy environment file
        run: cp .env.testing .env

      - name: Generate application key
        run: php artisan key:generate

      - name: Run migrations
        run: php artisan migrate --force

      - name: Run tests with coverage
        run: |
          php artisan test --coverage-clover=coverage.xml

      - name: Check coverage threshold
        run: |
          COVERAGE=$(php -r "echo round(simplexml_load_file('coverage.xml')->project->directory->totals->lines['percent']);")
          echo "Coverage: $COVERAGE%"
          if [ "$COVERAGE" -lt 80 ]; then
            echo "Coverage is below 80%"
            exit 1
          fi

      - name: Upload coverage to Codecov
        uses: codecov/codecov-action@v3
        with:
          files: backend/coverage.xml
          fail_ci_if_error: true

  security:
    name: Security Scan
    runs-on: ubuntu-latest
    needs: lint
    defaults:
      run:
        working-directory: backend

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Security audit
        run: composer audit

      - name: Run security checker
        uses: symfonycorp/security-checker-action@v5

  build:
    name: Build Docker Image
    runs-on: ubuntu-latest
    needs: [test, security]
    if: github.ref == 'refs/heads/main' || github.ref == 'refs/heads/develop'

    steps:
      - uses: actions/checkout@v4

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Login to Container Registry
        uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Build and push
        uses: docker/build-push-action@v5
        with:
          context: ./backend
          push: true
          tags: |
            ghcr.io/${{ github.repository }}/backend:${{ github.sha }}
            ghcr.io/${{ github.repository }}/backend:${{ github.ref_name }}
          cache-from: type=gha
          cache-to: type=gha,mode=max

  deploy-staging:
    name: Deploy to Staging
    runs-on: ubuntu-latest
    needs: build
    if: github.ref == 'refs/heads/develop'
    environment: staging

    steps:
      - name: Deploy to staging
        run: |
          # Trigger deployment via webhook or kubectl
          curl -X POST ${{ secrets.STAGING_DEPLOY_WEBHOOK }} \
            -H "Authorization: Bearer ${{ secrets.DEPLOY_TOKEN }}" \
            -d '{"image": "ghcr.io/${{ github.repository }}/backend:${{ github.sha }}"}'

  deploy-production:
    name: Deploy to Production
    runs-on: ubuntu-latest
    needs: build
    if: github.ref == 'refs/heads/main'
    environment: production

    steps:
      - name: Deploy to production
        run: |
          curl -X POST ${{ secrets.PRODUCTION_DEPLOY_WEBHOOK }} \
            -H "Authorization: Bearer ${{ secrets.DEPLOY_TOKEN }}" \
            -d '{"image": "ghcr.io/${{ github.repository }}/backend:${{ github.sha }}"}'
```

### 11.3 GitHub Actions - Frontend Pipeline

```yaml
# .github/workflows/frontend-ci.yml
name: Frontend CI

on:
  push:
    branches: [main, develop]
    paths:
      - 'frontend/**'
      - '.github/workflows/frontend-ci.yml'
  pull_request:
    branches: [main, develop]
    paths:
      - 'frontend/**'

env:
  NODE_VERSION: '20'

jobs:
  lint:
    name: Lint & Type Check
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: frontend

    steps:
      - uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: ${{ env.NODE_VERSION }}
          cache: 'npm'
          cache-dependency-path: frontend/package-lock.json

      - name: Install dependencies
        run: npm ci

      - name: Run ESLint
        run: npm run lint

      - name: Run Prettier check
        run: npm run format:check

      - name: Type check
        run: npm run type-check

  test:
    name: Unit Tests
    runs-on: ubuntu-latest
    needs: lint
    defaults:
      run:
        working-directory: frontend

    steps:
      - uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: ${{ env.NODE_VERSION }}
          cache: 'npm'
          cache-dependency-path: frontend/package-lock.json

      - name: Install dependencies
        run: npm ci

      - name: Run unit tests
        run: npm run test:unit -- --coverage

      - name: Check coverage threshold
        run: |
          # Vitest outputs coverage summary
          npm run test:coverage -- --reporter=json-summary

  e2e:
    name: E2E Tests
    runs-on: ubuntu-latest
    needs: lint
    defaults:
      run:
        working-directory: frontend

    steps:
      - uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: ${{ env.NODE_VERSION }}
          cache: 'npm'
          cache-dependency-path: frontend/package-lock.json

      - name: Install dependencies
        run: npm ci

      - name: Install Playwright browsers
        run: npx playwright install --with-deps

      - name: Build application
        run: npm run build
        env:
          VITE_API_URL: http://localhost:8000

      - name: Run E2E tests
        run: npm run test:e2e

      - name: Upload test results
        if: failure()
        uses: actions/upload-artifact@v3
        with:
          name: playwright-report
          path: frontend/playwright-report/

  build:
    name: Build
    runs-on: ubuntu-latest
    needs: [test, e2e]
    if: github.ref == 'refs/heads/main' || github.ref == 'refs/heads/develop'
    defaults:
      run:
        working-directory: frontend

    steps:
      - uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: ${{ env.NODE_VERSION }}
          cache: 'npm'
          cache-dependency-path: frontend/package-lock.json

      - name: Install dependencies
        run: npm ci

      - name: Build for production
        run: npm run build
        env:
          VITE_API_URL: ${{ vars.API_URL }}

      - name: Upload build artifacts
        uses: actions/upload-artifact@v3
        with:
          name: frontend-dist
          path: frontend/dist/
```

### 11.4 Database Migration Pipeline

```yaml
# .github/workflows/migration-check.yml
name: Migration Safety Check

on:
  pull_request:
    paths:
      - 'backend/database/migrations/**'

jobs:
  migration-check:
    name: Check Migration Safety
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: secret
          MYSQL_DATABASE: bizsocials_test
        ports:
          - 3306:3306

    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - name: Install dependencies
        run: |
          cd backend
          composer install --prefer-dist --no-progress

      - name: Run migrations (fresh)
        run: |
          cd backend
          php artisan migrate:fresh --force

      - name: Run migrations (rollback test)
        run: |
          cd backend
          php artisan migrate:rollback --force
          php artisan migrate --force

      - name: Check for destructive operations
        run: |
          # Check new migration files for potentially dangerous operations
          git diff origin/develop --name-only -- 'backend/database/migrations/*.php' | while read file; do
            if grep -E 'dropColumn|dropTable|drop\(' "$file"; then
              echo "WARNING: $file contains potentially destructive operations"
              echo "Please ensure you have a rollback plan"
            fi
          done
```

---

## 12. Docker & Containerization

### 12.1 Backend Dockerfile

```dockerfile
# backend/Dockerfile
FROM php:8.3-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    mysql-client \
    redis

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Development stage
FROM base AS development

# Install development dependencies
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY docker/php/php-dev.ini /usr/local/etc/php/conf.d/custom.ini

# Production stage
FROM base AS production

# Copy optimized PHP config
COPY docker/php/php-prod.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy application code
COPY --chown=www-data:www-data . .

# Install production dependencies only
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Cache Laravel config/routes
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
```

### 12.2 Frontend Dockerfile

```dockerfile
# frontend/Dockerfile

# Build stage
FROM node:20-alpine AS builder

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci

# Copy source code
COPY . .

# Build arguments for environment
ARG VITE_API_URL
ENV VITE_API_URL=$VITE_API_URL

# Build application
RUN npm run build

# Production stage
FROM nginx:alpine AS production

# Copy nginx configuration
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy built assets
COPY --from=builder /app/dist /usr/share/nginx/html

# Add healthcheck
HEALTHCHECK --interval=30s --timeout=3s \
    CMD wget --quiet --tries=1 --spider http://localhost/ || exit 1

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
```

### 12.3 Docker Compose (Development)

```yaml
# docker-compose.yml
version: '3.8'

services:
  # PHP Application
  app:
    build:
      context: ./backend
      target: development
    volumes:
      - ./backend:/var/www/html
      - ./backend/docker/php/php-dev.ini:/usr/local/etc/php/conf.d/custom.ini
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
      - DB_HOST=mysql
      - DB_DATABASE=bizsocials
      - DB_USERNAME=root
      - DB_PASSWORD=secret
      - REDIS_HOST=redis
      - QUEUE_CONNECTION=redis
    depends_on:
      - mysql
      - redis
    networks:
      - bizsocials

  # Nginx Web Server
  nginx:
    image: nginx:alpine
    ports:
      - "8000:80"
    volumes:
      - ./backend:/var/www/html
      - ./backend/docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app
    networks:
      - bizsocials

  # MySQL Database
  mysql:
    image: mysql:8.0
    ports:
      - "3306:3306"
    environment:
      - MYSQL_ROOT_PASSWORD=secret
      - MYSQL_DATABASE=bizsocials
    volumes:
      - mysql_data:/var/lib/mysql
      - ./backend/docker/mysql/my.cnf:/etc/mysql/conf.d/my.cnf
    networks:
      - bizsocials

  # Redis Cache & Queue
  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    networks:
      - bizsocials

  # Queue Worker
  queue:
    build:
      context: ./backend
      target: development
    command: php artisan queue:work --tries=3 --timeout=90
    volumes:
      - ./backend:/var/www/html
    environment:
      - APP_ENV=local
      - DB_HOST=mysql
      - REDIS_HOST=redis
    depends_on:
      - app
      - redis
    networks:
      - bizsocials

  # Scheduler
  scheduler:
    build:
      context: ./backend
      target: development
    command: php artisan schedule:work
    volumes:
      - ./backend:/var/www/html
    environment:
      - APP_ENV=local
      - DB_HOST=mysql
      - REDIS_HOST=redis
    depends_on:
      - app
    networks:
      - bizsocials

  # Frontend (Development)
  frontend:
    image: node:20-alpine
    working_dir: /app
    command: npm run dev -- --host
    ports:
      - "5173:5173"
    volumes:
      - ./frontend:/app
      - /app/node_modules
    environment:
      - VITE_API_URL=http://localhost:8000
    networks:
      - bizsocials

  # Mailhog (Email Testing)
  mailhog:
    image: mailhog/mailhog
    ports:
      - "1025:1025"
      - "8025:8025"
    networks:
      - bizsocials

volumes:
  mysql_data:
  redis_data:

networks:
  bizsocials:
    driver: bridge
```

### 12.4 Docker Compose (Production)

```yaml
# docker-compose.prod.yml
version: '3.8'

services:
  app:
    image: ghcr.io/bizsocials/backend:${IMAGE_TAG:-latest}
    restart: always
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - DB_HOST=${DB_HOST}
      - DB_DATABASE=${DB_DATABASE}
      - DB_USERNAME=${DB_USERNAME}
      - DB_PASSWORD=${DB_PASSWORD}
      - REDIS_HOST=${REDIS_HOST}
      - REDIS_PASSWORD=${REDIS_PASSWORD}
    healthcheck:
      test: ["CMD", "php-fpm-healthcheck"]
      interval: 30s
      timeout: 5s
      retries: 3
    deploy:
      replicas: 3
      resources:
        limits:
          cpus: '1'
          memory: 512M
        reservations:
          cpus: '0.5'
          memory: 256M

  nginx:
    image: ghcr.io/bizsocials/nginx:${IMAGE_TAG:-latest}
    restart: always
    ports:
      - "80:80"
      - "443:443"
    depends_on:
      - app
    deploy:
      replicas: 2

  queue:
    image: ghcr.io/bizsocials/backend:${IMAGE_TAG:-latest}
    restart: always
    command: php artisan queue:work --tries=3 --timeout=90 --sleep=3
    environment:
      - APP_ENV=production
    deploy:
      replicas: 2

  scheduler:
    image: ghcr.io/bizsocials/backend:${IMAGE_TAG:-latest}
    restart: always
    command: php artisan schedule:work
    deploy:
      replicas: 1
```

### 12.5 Kubernetes Manifests (Production)

```yaml
# k8s/backend-deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: bizsocials-backend
  labels:
    app: bizsocials
    component: backend
spec:
  replicas: 3
  selector:
    matchLabels:
      app: bizsocials
      component: backend
  template:
    metadata:
      labels:
        app: bizsocials
        component: backend
    spec:
      containers:
        - name: php-fpm
          image: ghcr.io/bizsocials/backend:latest
          ports:
            - containerPort: 9000
          envFrom:
            - secretRef:
                name: bizsocials-secrets
            - configMapRef:
                name: bizsocials-config
          resources:
            requests:
              memory: "256Mi"
              cpu: "250m"
            limits:
              memory: "512Mi"
              cpu: "500m"
          readinessProbe:
            exec:
              command:
                - php-fpm-healthcheck
            initialDelaySeconds: 5
            periodSeconds: 10
          livenessProbe:
            exec:
              command:
                - php-fpm-healthcheck
            initialDelaySeconds: 15
            periodSeconds: 20
          volumeMounts:
            - name: storage
              mountPath: /var/www/html/storage/app
      volumes:
        - name: storage
          persistentVolumeClaim:
            claimName: bizsocials-storage

---
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: bizsocials-backend-hpa
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: bizsocials-backend
  minReplicas: 3
  maxReplicas: 10
  metrics:
    - type: Resource
      resource:
        name: cpu
        target:
          type: Utilization
          averageUtilization: 70
    - type: Resource
      resource:
        name: memory
        target:
          type: Utilization
          averageUtilization: 80
```

---

## 13. Application Performance Monitoring

### 13.1 APM Strategy

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        APM OBSERVABILITY STACK                               │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────┐   ┌─────────────┐     │
│  │   Metrics   │   │   Traces    │   │    Logs     │   │   Alerts    │     │
│  │ (Datadog/   │   │ (Datadog/   │   │ (ELK/       │   │ (PagerDuty/ │     │
│  │  Prometheus)│   │  Jaeger)    │   │  CloudWatch)│   │  OpsGenie)  │     │
│  └──────┬──────┘   └──────┬──────┘   └──────┬──────┘   └──────┬──────┘     │
│         │                 │                 │                 │             │
│         └─────────────────┴─────────────────┴─────────────────┘             │
│                                   │                                         │
│                           ┌───────┴───────┐                                 │
│                           │   Dashboard   │                                 │
│                           │   (Grafana/   │                                 │
│                           │    Datadog)   │                                 │
│                           └───────────────┘                                 │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 13.2 Key Metrics to Track

```
┌─────────────────────────────────────────────────────────────────┐
│                       METRICS CATEGORIES                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  APPLICATION METRICS                                            │
│  ├── Request rate (requests/second)                             │
│  ├── Response time (p50, p95, p99)                              │
│  ├── Error rate (4xx, 5xx)                                      │
│  ├── Throughput by endpoint                                     │
│  └── Active users (concurrent sessions)                         │
│                                                                 │
│  BUSINESS METRICS                                               │
│  ├── Posts published per hour                                   │
│  ├── OAuth connections per day                                  │
│  ├── API calls to social platforms                              │
│  ├── AI suggestions generated                                   │
│  └── Workspace creations                                        │
│                                                                 │
│  INFRASTRUCTURE METRICS                                         │
│  ├── CPU utilization                                            │
│  ├── Memory usage                                               │
│  ├── Disk I/O                                                   │
│  ├── Network throughput                                         │
│  └── Container restarts                                         │
│                                                                 │
│  DATABASE METRICS                                               │
│  ├── Query execution time                                       │
│  ├── Connection pool usage                                      │
│  ├── Slow query count                                           │
│  ├── Replication lag                                            │
│  └── Table size growth                                          │
│                                                                 │
│  QUEUE METRICS                                                  │
│  ├── Jobs processed per minute                                  │
│  ├── Queue depth                                                │
│  ├── Failed jobs count                                          │
│  ├── Job processing time                                        │
│  └── Worker count                                               │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 13.3 Laravel Telescope (Development)

```php
// config/telescope.php
return [
    'enabled' => env('TELESCOPE_ENABLED', false),

    'watchers' => [
        Watchers\QueryWatcher::class => [
            'enabled' => true,
            'slow' => 100, // Log queries over 100ms
        ],
        Watchers\RequestWatcher::class => [
            'enabled' => true,
            'size_limit' => 64, // KB
        ],
        Watchers\ExceptionWatcher::class => true,
        Watchers\JobWatcher::class => true,
        Watchers\LogWatcher::class => true,
        Watchers\MailWatcher::class => true,
        Watchers\NotificationWatcher::class => true,
        Watchers\RedisWatcher::class => true,
    ],
];
```

### 13.4 Datadog Integration

```php
// config/datadog.php
return [
    'enabled' => env('DATADOG_ENABLED', false),
    'api_key' => env('DATADOG_API_KEY'),
    'app_key' => env('DATADOG_APP_KEY'),
    'service' => 'bizsocials-api',
    'env' => env('APP_ENV'),
    'version' => env('APP_VERSION', '1.0.0'),
];

// app/Providers/AppServiceProvider.php
public function boot(): void
{
    if (config('datadog.enabled')) {
        // Custom metrics
        \DDTrace\trace_function('App\Services\Publishing\PublishingService::publish', function ($span) {
            $span->name = 'publishing.publish';
            $span->service = 'bizsocials-api';
            $span->type = 'custom';
        });
    }
}
```

### 13.5 Custom Metrics Service

```php
<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Log;

final class MetricsService
{
    public function increment(string $metric, array $tags = []): void
    {
        if (config('datadog.enabled')) {
            \DogStatsd::increment($metric, $this->formatTags($tags));
        }

        Log::debug("Metric: {$metric}", $tags);
    }

    public function gauge(string $metric, float $value, array $tags = []): void
    {
        if (config('datadog.enabled')) {
            \DogStatsd::gauge($metric, $value, $this->formatTags($tags));
        }
    }

    public function timing(string $metric, float $milliseconds, array $tags = []): void
    {
        if (config('datadog.enabled')) {
            \DogStatsd::timing($metric, $milliseconds, $this->formatTags($tags));
        }
    }

    public function histogram(string $metric, float $value, array $tags = []): void
    {
        if (config('datadog.enabled')) {
            \DogStatsd::histogram($metric, $value, $this->formatTags($tags));
        }
    }

    private function formatTags(array $tags): array
    {
        return array_map(
            fn ($key, $value) => "{$key}:{$value}",
            array_keys($tags),
            array_values($tags)
        );
    }
}

// Usage in services
$this->metrics->increment('posts.published', [
    'workspace_id' => $post->workspace_id,
    'platform' => $target->platform,
]);

$this->metrics->timing('publishing.duration', $duration, [
    'platform' => $target->platform,
]);
```

---

## 14. Error Tracking & Alerting

### 14.1 Sentry Integration

```php
// config/sentry.php
return [
    'dsn' => env('SENTRY_DSN'),
    'environment' => env('APP_ENV'),
    'release' => env('APP_VERSION'),
    'sample_rate' => 1.0,
    'traces_sample_rate' => env('APP_ENV') === 'production' ? 0.2 : 1.0,

    'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
        // Filter out sensitive data
        if ($event->getRequest()) {
            $request = $event->getRequest();
            $data = $request->getData();

            // Remove sensitive fields
            unset($data['password'], $data['token'], $data['api_key']);

            $event->setRequest($request->setData($data));
        }

        return $event;
    },
];

// app/Exceptions/Handler.php
public function register(): void
{
    $this->reportable(function (Throwable $e) {
        if ($this->shouldReport($e) && app()->bound('sentry')) {
            \Sentry\captureException($e);
        }
    });
}
```

### 14.2 Frontend Error Tracking

```typescript
// src/plugins/sentry.ts
import * as Sentry from '@sentry/vue'
import type { App } from 'vue'
import router from '@/router'

export function initSentry(app: App) {
  if (import.meta.env.VITE_SENTRY_DSN) {
    Sentry.init({
      app,
      dsn: import.meta.env.VITE_SENTRY_DSN,
      environment: import.meta.env.MODE,
      release: import.meta.env.VITE_APP_VERSION,

      integrations: [
        Sentry.browserTracingIntegration({ router }),
        Sentry.replayIntegration({
          maskAllText: true,
          blockAllMedia: true,
        }),
      ],

      tracesSampleRate: import.meta.env.PROD ? 0.2 : 1.0,
      replaysSessionSampleRate: 0.1,
      replaysOnErrorSampleRate: 1.0,

      beforeSend(event) {
        // Filter sensitive data
        if (event.request?.data) {
          delete event.request.data.password
          delete event.request.data.token
        }
        return event
      },
    })
  }
}

// Usage: Capture custom errors
Sentry.captureException(new Error('Custom error'))
Sentry.captureMessage('Something went wrong', 'error')

// Add context
Sentry.setUser({ id: user.id, email: user.email })
Sentry.setTag('workspace_id', workspaceId)
```

### 14.3 Alert Rules

```yaml
# alerts/rules.yaml
alerts:
  # Error rate alert
  - name: high_error_rate
    condition: error_rate > 5%
    window: 5m
    severity: critical
    channels: [pagerduty, slack]
    message: "Error rate is above 5% for the last 5 minutes"

  # Response time alert
  - name: slow_response_time
    condition: p95_response_time > 2000ms
    window: 5m
    severity: warning
    channels: [slack]
    message: "P95 response time exceeds 2 seconds"

  # Queue depth alert
  - name: queue_backlog
    condition: queue_depth > 1000
    window: 10m
    severity: warning
    channels: [slack]
    message: "Queue depth exceeds 1000 jobs"

  # Failed jobs alert
  - name: failed_jobs_spike
    condition: failed_jobs_count > 50
    window: 15m
    severity: critical
    channels: [pagerduty, slack]
    message: "More than 50 failed jobs in 15 minutes"

  # Database connection alert
  - name: db_connection_pool_exhausted
    condition: db_connections > 90%
    window: 5m
    severity: critical
    channels: [pagerduty]
    message: "Database connection pool nearly exhausted"

  # Publishing failure alert
  - name: publishing_failures
    condition: publishing_failure_rate > 10%
    window: 15m
    severity: warning
    channels: [slack]
    message: "Publishing failure rate exceeds 10%"

  # Memory alert
  - name: high_memory_usage
    condition: memory_usage > 85%
    window: 10m
    severity: warning
    channels: [slack]
    message: "Memory usage exceeds 85%"
```

### 14.4 Structured Logging

```php
<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;

final class StructuredLogFormatter extends JsonFormatter
{
    public function format(array $record): string
    {
        $record['extra'] = array_merge($record['extra'], [
            'service' => 'bizsocials-api',
            'environment' => config('app.env'),
            'version' => config('app.version'),
            'request_id' => request()->header('X-Request-ID'),
            'workspace_id' => app()->has('workspace') ? app('workspace')->id : null,
            'user_id' => auth()->id(),
        ]);

        return parent::format($record);
    }
}

// Usage
Log::info('Post published successfully', [
    'post_id' => $post->id,
    'platform' => $target->platform,
    'duration_ms' => $duration,
]);

// Outputs structured JSON:
// {
//   "message": "Post published successfully",
//   "level": "info",
//   "extra": {
//     "service": "bizsocials-api",
//     "environment": "production",
//     "workspace_id": "uuid",
//     "user_id": "uuid",
//     "request_id": "uuid"
//   },
//   "context": {
//     "post_id": "uuid",
//     "platform": "LINKEDIN",
//     "duration_ms": 1234
//   }
// }
```

---

## 15. Security Practices

### 15.1 Security Checklist

```
┌─────────────────────────────────────────────────────────────────┐
│                    SECURITY REQUIREMENTS                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  AUTHENTICATION & AUTHORIZATION                                 │
│  ☐ JWT tokens with short expiry (15 min access, 7 day refresh) │
│  ☐ Secure token storage (httpOnly cookies or secure storage)   │
│  ☐ Rate limiting on auth endpoints                              │
│  ☐ Account lockout after failed attempts                        │
│  ☐ Role-based access control enforced at API level              │
│                                                                 │
│  INPUT VALIDATION                                               │
│  ☐ All inputs validated server-side                             │
│  ☐ SQL injection prevention (parameterized queries)             │
│  ☐ XSS prevention (output encoding)                             │
│  ☐ CSRF protection on state-changing operations                 │
│  ☐ File upload validation (type, size, content)                 │
│                                                                 │
│  DATA PROTECTION                                                │
│  ☐ Sensitive data encrypted at rest                             │
│  ☐ TLS 1.3 for all communications                               │
│  ☐ OAuth tokens encrypted in database                           │
│  ☐ PII handling compliant with GDPR                             │
│  ☐ Audit logging for sensitive operations                       │
│                                                                 │
│  INFRASTRUCTURE                                                 │
│  ☐ Secrets in vault/secret manager (never in code)              │
│  ☐ Container images scanned for vulnerabilities                 │
│  ☐ Dependency vulnerability scanning in CI                      │
│  ☐ Network policies restricting container communication         │
│  ☐ Regular security updates                                     │
│                                                                 │
│  MULTI-TENANCY                                                  │
│  ☐ Workspace isolation enforced at all layers                   │
│  ☐ Cross-tenant access prevented                                │
│  ☐ Tenant context validated in every request                    │
│  ☐ Background jobs carry workspace context                      │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 15.2 Security Headers

```php
// app/Http/Middleware/SecurityHeaders.php
public function handle(Request $request, Closure $next): Response
{
    $response = $next($request);

    return $response
        ->header('X-Content-Type-Options', 'nosniff')
        ->header('X-Frame-Options', 'DENY')
        ->header('X-XSS-Protection', '1; mode=block')
        ->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')
        ->header('Content-Security-Policy', $this->getCSP())
        ->header('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->header('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
}

private function getCSP(): string
{
    return implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
        "style-src 'self' 'unsafe-inline'",
        "img-src 'self' data: https:",
        "font-src 'self'",
        "connect-src 'self' https://api.bizsocials.io",
        "frame-ancestors 'none'",
    ]);
}
```

### 15.3 OAuth Token Encryption

```php
<?php

namespace App\Services\Security;

use Illuminate\Contracts\Encryption\Encrypter;

final class TokenEncryptionService
{
    public function __construct(
        private readonly Encrypter $encrypter,
    ) {}

    public function encrypt(string $token): string
    {
        return $this->encrypter->encrypt($token);
    }

    public function decrypt(string $encryptedToken): string
    {
        return $this->encrypter->decrypt($encryptedToken);
    }
}

// Usage in SocialAccount model
protected static function booted(): void
{
    static::saving(function (SocialAccount $account) {
        if ($account->isDirty('access_token')) {
            $account->access_token = app(TokenEncryptionService::class)
                ->encrypt($account->access_token);
        }
        if ($account->isDirty('refresh_token') && $account->refresh_token) {
            $account->refresh_token = app(TokenEncryptionService::class)
                ->encrypt($account->refresh_token);
        }
    });
}

public function getDecryptedAccessToken(): string
{
    return app(TokenEncryptionService::class)->decrypt($this->access_token);
}
```

### 15.4 Dependency Scanning

```yaml
# .github/workflows/security-scan.yml
name: Security Scan

on:
  schedule:
    - cron: '0 6 * * *'  # Daily at 6 AM
  push:
    branches: [main, develop]

jobs:
  backend-scan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: PHP Security Audit
        run: |
          cd backend
          composer audit

      - name: SAST Scan
        uses: returntocorp/semgrep-action@v1
        with:
          config: >-
            p/php
            p/security-audit

  frontend-scan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: NPM Audit
        run: |
          cd frontend
          npm audit --audit-level=high

      - name: Snyk Scan
        uses: snyk/actions/node@master
        with:
          args: --severity-threshold=high
        env:
          SNYK_TOKEN: ${{ secrets.SNYK_TOKEN }}

  container-scan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Build image
        run: docker build -t bizsocials-backend:scan ./backend

      - name: Trivy Scan
        uses: aquasecurity/trivy-action@master
        with:
          image-ref: bizsocials-backend:scan
          severity: 'CRITICAL,HIGH'
          exit-code: '1'
```

---

## 16. Code Review Process

### 16.1 Review Checklist

```
┌─────────────────────────────────────────────────────────────────┐
│                   CODE REVIEW CHECKLIST                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  FUNCTIONALITY                                                  │
│  ☐ Code accomplishes the stated goal                            │
│  ☐ Edge cases are handled                                       │
│  ☐ Error handling is appropriate                                │
│  ☐ No obvious bugs or logic errors                              │
│                                                                 │
│  CODE QUALITY                                                   │
│  ☐ Follows project coding standards                             │
│  ☐ No code duplication                                          │
│  ☐ Functions/methods are focused (single responsibility)        │
│  ☐ Naming is clear and consistent                               │
│  ☐ No commented-out code                                        │
│                                                                 │
│  TESTING                                                        │
│  ☐ Tests cover the new functionality                            │
│  ☐ Tests cover edge cases                                       │
│  ☐ Tests are maintainable and readable                          │
│  ☐ No flaky tests introduced                                    │
│                                                                 │
│  SECURITY                                                       │
│  ☐ No hardcoded secrets                                         │
│  ☐ Input validation present                                     │
│  ☐ Authorization checks in place                                │
│  ☐ Workspace isolation maintained                               │
│                                                                 │
│  PERFORMANCE                                                    │
│  ☐ No N+1 queries                                               │
│  ☐ Appropriate indexes for new queries                          │
│  ☐ No obvious performance issues                                │
│  ☐ Large datasets handled appropriately                         │
│                                                                 │
│  DOCUMENTATION                                                  │
│  ☐ Complex logic is commented                                   │
│  ☐ API changes documented                                       │
│  ☐ README updated if needed                                     │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 16.2 Review Response Times

| PR Size | Expected Review Time |
|---------|---------------------|
| XS (< 50 lines) | Same day |
| S (50-200 lines) | 1 business day |
| M (200-500 lines) | 2 business days |
| L (500+ lines) | Should be split |

### 16.3 Approval Requirements

| Branch Target | Required Approvals | Required Checks |
|---------------|-------------------|-----------------|
| `develop` | 1 developer | CI passing |
| `main` | 2 developers (1 senior) | CI + security scan |
| `release/*` | 2 developers + tech lead | All checks |

---

## 17. Release Management

### 17.1 Release Process

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          RELEASE WORKFLOW                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  1. CODE FREEZE                                                             │
│     ├── Create release branch from develop                                  │
│     ├── No new features, only bug fixes                                     │
│     └── QA begins regression testing                                        │
│                                                                             │
│  2. QA & STABILIZATION                                                      │
│     ├── Full regression test suite                                          │
│     ├── Bug fixes merged to release branch                                  │
│     ├── Performance testing                                                 │
│     └── Security scan                                                       │
│                                                                             │
│  3. STAGING DEPLOYMENT                                                      │
│     ├── Deploy to staging environment                                       │
│     ├── Smoke tests                                                         │
│     ├── UAT sign-off                                                        │
│     └── Final go/no-go decision                                             │
│                                                                             │
│  4. PRODUCTION DEPLOYMENT                                                   │
│     ├── Merge release branch to main                                        │
│     ├── Tag release (v1.x.x)                                                │
│     ├── Deploy to production                                                │
│     ├── Monitor metrics and errors                                          │
│     └── Merge main back to develop                                          │
│                                                                             │
│  5. POST-RELEASE                                                            │
│     ├── Monitor for 24-48 hours                                             │
│     ├── Gather initial feedback                                             │
│     └── Retrospective                                                       │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 17.2 Versioning Strategy

```
MAJOR.MINOR.PATCH

Examples:
- 1.0.0 → Initial release
- 1.1.0 → New feature (backward compatible)
- 1.1.1 → Bug fix
- 2.0.0 → Breaking changes

API Versioning:
- v1 → /api/v1/*
- v2 → /api/v2/* (when breaking changes needed)
```

### 17.3 Rollback Procedure

```
┌─────────────────────────────────────────────────────────────────┐
│                    ROLLBACK PROCEDURE                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  DECISION CRITERIA (rollback if ANY):                           │
│  • Error rate > 10%                                             │
│  • Critical functionality broken                                │
│  • Data corruption detected                                     │
│  • P95 response time > 5s                                       │
│                                                                 │
│  IMMEDIATE ACTIONS:                                             │
│  1. Alert on-call engineer                                      │
│  2. Assess severity (P1/P2/P3)                                  │
│  3. Decision: fix forward or rollback                           │
│                                                                 │
│  ROLLBACK STEPS:                                                │
│  1. kubectl rollout undo deployment/bizsocials-backend          │
│     OR                                                          │
│     Deploy previous Docker tag                                  │
│                                                                 │
│  2. Verify rollback successful:                                 │
│     - Check pod health                                          │
│     - Verify error rate dropping                                │
│     - Test critical paths                                       │
│                                                                 │
│  3. Database rollback (if needed):                              │
│     - Run down migration                                        │
│     - Restore from backup (last resort)                         │
│                                                                 │
│  4. Communication:                                              │
│     - Notify stakeholders                                       │
│     - Update status page                                        │
│     - Create incident report                                    │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 17.4 Feature Flags

```php
<?php

namespace App\Services;

final class FeatureFlagService
{
    public function isEnabled(string $feature, ?string $workspaceId = null): bool
    {
        $flags = config('features');

        if (!isset($flags[$feature])) {
            return false;
        }

        $flag = $flags[$feature];

        // Global flag
        if (is_bool($flag)) {
            return $flag;
        }

        // Percentage rollout
        if (isset($flag['percentage']) && $workspaceId) {
            $hash = crc32($workspaceId . $feature);
            return ($hash % 100) < $flag['percentage'];
        }

        // Workspace whitelist
        if (isset($flag['workspaces']) && $workspaceId) {
            return in_array($workspaceId, $flag['workspaces']);
        }

        return $flag['enabled'] ?? false;
    }
}

// config/features.php
return [
    'new_calendar_ui' => [
        'enabled' => true,
        'percentage' => 50, // 50% rollout
    ],
    'ai_suggestions_v2' => [
        'enabled' => true,
        'workspaces' => ['uuid-1', 'uuid-2'], // Beta testers only
    ],
    'bulk_scheduling' => false, // Disabled globally
];

// Usage
if ($featureFlags->isEnabled('new_calendar_ui', $workspace->id)) {
    // Show new UI
}
```

---

## 18. Document Control

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | Feb 2026 | Solution Architecture | Initial SDLC document |

---

**END OF COMPREHENSIVE SDLC PLAN**
