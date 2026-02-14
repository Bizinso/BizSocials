# BizSocials Docker Setup Guide

This guide will help you set up and run the BizSocials platform using Docker.

## Prerequisites

- Docker Desktop installed and running
- Docker Compose (included with Docker Desktop)
- At least 4GB RAM allocated to Docker
- Ports available: 8080, 3306, 6379, 6380, 7700, 8025, 9000, 9001, 6001

## Quick Start (One Command Setup)

```bash
make setup
```

This single command will:
1. Copy `.env.example` to `.env` (if not exists)
2. Start all Docker services
3. Wait for services to be healthy
4. Install Composer dependencies
5. Generate application key
6. Run database migrations
7. Seed the database
8. Set up storage links
9. Clear all caches

**Total time: ~3-5 minutes**

## Step-by-Step Setup (Manual)

If you prefer to run each step manually:

### 1. Copy Environment File

```bash
make setup-env
```

Or manually:
```bash
cp backend/.env.example backend/.env
```

### 2. Start Docker Services

```bash
make up
```

This starts all services:
- PHP-FPM Application
- Nginx Web Server
- MySQL Database
- Redis Cache
- MinIO (S3 storage)
- MailHog (Email testing)
- Meilisearch (Search engine)
- Laravel Horizon (Queue worker)
- Laravel Reverb (WebSocket server)
- Laravel Scheduler

### 3. Install Dependencies

```bash
make composer cmd="install --no-interaction --prefer-dist --optimize-autoloader"
```

### 4. Generate Application Key

```bash
make artisan cmd="key:generate"
```

### 5. Run Migrations

```bash
make migrate
```

### 6. Seed Database (Optional)

```bash
make seed
```

### 7. Create Storage Link

```bash
make artisan cmd="storage:link"
```

## Accessing Services

After setup, you can access:

| Service | URL | Credentials |
|---------|-----|-------------|
| API | http://localhost:8080 | - |
| MailHog (Email) | http://localhost:8025 | - |
| MinIO (S3) | http://localhost:9001 | minioadmin / minioadmin |
| Meilisearch | http://localhost:7700 | masterkey |
| MySQL | localhost:3306 | bizsocials / bizsocials |
| Redis | localhost:6380 | - |

## Running Tests

### All Tests
```bash
make test
```

### Unit Tests Only
```bash
make test-unit
```

### Feature Tests Only
```bash
make test-feature
```

### Property Tests Only
```bash
make test-properties
```

### Tests with Coverage
```bash
make test-cov
```

## Common Commands

### View Logs
```bash
make logs          # All services
make logs-app      # App only
```

### Enter Container Shell
```bash
make shell
```

### Run Artisan Commands
```bash
make artisan cmd="migrate"
make artisan cmd="db:seed"
make artisan cmd="tinker"
```

### Database Commands
```bash
make migrate       # Run migrations
make seed          # Seed database
make fresh         # Fresh migrate with seeds
make mysql         # Enter MySQL CLI
```

### Cache Commands
```bash
make cache-clear   # Clear all caches
make optimize      # Optimize application
```

### Code Quality
```bash
make lint          # Fix code style
make lint-check    # Check code style
make analyze       # Run PHPStan analysis
```

### Stop Services
```bash
make down          # Stop all services
make clean         # Stop and remove volumes
```

## Troubleshooting

### Port Already in Use

If you get "port already in use" errors:

1. Check what's using the port:
```bash
lsof -i :8080  # or whichever port
```

2. Stop the conflicting service or change the port in `docker-compose.yml`

### Services Not Starting

1. Check Docker Desktop is running
2. Check available disk space
3. View logs:
```bash
make logs
```

### Composer Install Fails

If you see Pusher errors during `composer install`:

1. Ensure your `.env` has these values:
```
PUSHER_APP_ID=dummy
PUSHER_APP_KEY=dummy
PUSHER_APP_SECRET=dummy
```

2. Run setup again:
```bash
make setup
```

### Database Connection Issues

1. Wait for MySQL to be fully ready (can take 30-60 seconds on first start)
2. Check MySQL is healthy:
```bash
docker compose ps
```

3. Restart services:
```bash
make restart
```

### Permission Issues

If you encounter permission errors:

```bash
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

## Testing Your Setup

After setup, verify everything works:

### 1. Check API Health
```bash
curl http://localhost:8080/api/health
```

### 2. Run Tests
```bash
make test-unit
```

### 3. Check Services
```bash
make ps
```

All services should show "Up" or "Up (healthy)"

### 4. Check Horizon
```bash
make horizon-status
```

## Development Workflow

### Daily Workflow

1. Start services:
```bash
make up
```

2. Make your changes

3. Run tests:
```bash
make test
```

4. Check code quality:
```bash
make lint
make analyze
```

5. Stop services when done:
```bash
make down
```

### After Pulling Changes

```bash
make down
make up
make composer cmd="install"
make migrate
make cache-clear
make test
```

## File Structure

```
BizSocials/
├── backend/              # Laravel backend
│   ├── app/             # Application code
│   ├── tests/           # Test files
│   ├── .env             # Environment config
│   └── Dockerfile       # PHP container config
├── docker/              # Docker configs
│   ├── nginx/          # Nginx config
│   ├── php/            # PHP config
│   └── mysql/          # MySQL init scripts
├── docker-compose.yml   # Docker services
├── Makefile            # Development commands
└── DOCKER_SETUP.md     # This file
```

## Next Steps

1. ✅ Run `make setup` to get started
2. ✅ Access http://localhost:8080 to verify API is running
3. ✅ Run `make test` to verify all tests pass
4. ✅ Check http://localhost:8025 to see test emails
5. ✅ Start developing!

## Getting Help

- View all available commands: `make help`
- Check logs: `make logs`
- Enter container: `make shell`
- Run artisan: `make artisan cmd="<command>"`

## Clean Slate

To completely reset everything:

```bash
make clean          # Stop and remove volumes
make setup          # Fresh setup
```

This will delete all data and start fresh.
