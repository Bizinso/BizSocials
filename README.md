# BizSocials - B2B Social Media Management Platform

[![Test Suite](https://github.com/YOUR_USERNAME/YOUR_REPO/actions/workflows/test.yml/badge.svg)](https://github.com/YOUR_USERNAME/YOUR_REPO/actions/workflows/test.yml)
[![codecov](https://codecov.io/gh/YOUR_USERNAME/YOUR_REPO/branch/main/graph/badge.svg)](https://codecov.io/gh/YOUR_USERNAME/YOUR_REPO)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-level%206-brightgreen.svg)](https://phpstan.org/)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue-3.x-green.svg)](https://vuejs.org)

BizSocials is a comprehensive B2B social media management platform built with Laravel 11 and Vue 3. It provides businesses with powerful tools to manage their social media presence across multiple platforms.

## Features

- 🔐 **Multi-tenant Architecture** - Secure workspace isolation for multiple organizations
- 📱 **Social Media Integration** - Connect and manage Facebook, Instagram, Twitter, LinkedIn, TikTok, and YouTube
- 📅 **Content Scheduling** - Plan and schedule posts across multiple platforms
- 📊 **Analytics & Reporting** - Comprehensive insights and performance metrics
- 💬 **Unified Inbox** - Manage all social media messages in one place
- ✅ **Approval Workflows** - Content review and approval processes
- 📞 **WhatsApp Business** - Integrated WhatsApp Business API support
- 🎫 **Support System** - Built-in ticketing and customer support
- 📚 **Knowledge Base** - Self-service documentation and help articles
- 💳 **Billing & Subscriptions** - Razorpay integration for payments

## Tech Stack

### Backend
- **Framework**: Laravel 11
- **PHP**: 8.2+
- **Database**: MySQL 8.0
- **Cache**: Redis
- **Queue**: Laravel Horizon
- **Search**: Meilisearch
- **Testing**: Pest PHP, PHPUnit
- **Static Analysis**: PHPStan/Larastan

### Frontend
- **Framework**: Vue 3 (Composition API)
- **UI Library**: PrimeVue
- **Build Tool**: Vite
- **Type Safety**: TypeScript
- **E2E Testing**: Playwright

## Getting Started

### Quick Start with Docker (Recommended)

The easiest way to get started is using Docker:

```bash
# One-command setup
make setup
```

Or use the quick start script:

```bash
./quick-start.sh
```

This will:
- Set up all services (MySQL, Redis, Meilisearch, etc.)
- Install dependencies
- Run migrations
- Seed the database
- Start the application

**Access the application:**
- API: http://localhost:8080
- MailHog (Email testing): http://localhost:8025
- MinIO (S3 storage): http://localhost:9001
- Meilisearch: http://localhost:7700

**Run tests:**
```bash
make test           # All tests
make test-unit      # Unit tests only
make test-feature   # Feature tests only
make test-properties # Property tests only
```

For detailed Docker setup instructions, see [DOCKER_SETUP.md](DOCKER_SETUP.md).

### Manual Installation (Without Docker)

### Prerequisites

- PHP 8.3 or higher
- Composer
- Node.js 20 or higher
- MySQL 8.0
- Redis
- Meilisearch

### Installation

1. Clone the repository:
```bash
git clone https://github.com/YOUR_USERNAME/YOUR_REPO.git
cd YOUR_REPO
```

2. Install backend dependencies:
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

3. Install frontend dependencies:
```bash
cd frontend
npm install
```

4. Start development servers:
```bash
# Backend (from backend directory)
php artisan serve

# Frontend (from frontend directory)
npm run dev
```

## Testing

### Backend Tests

```bash
cd backend

# Run all tests
php artisan test

# Run unit tests only
php artisan test --testsuite=Unit

# Run integration tests only
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

### Frontend E2E Tests

```bash
cd frontend

# Run E2E tests
npm run test:e2e

# Run E2E tests with UI
npm run test:e2e:ui

# Run E2E tests in headed mode
npm run test:e2e:headed
```

### Static Analysis

```bash
cd backend

# Run PHPStan
./vendor/bin/phpstan analyse

# Run Laravel Pint (code style)
./vendor/bin/pint
```

## CI/CD

The project uses GitHub Actions for continuous integration and deployment:

- **Unit Tests**: Run on every push and pull request
- **Integration Tests**: Run with MySQL service
- **E2E Tests**: Full browser testing with Playwright
- **Static Analysis**: PHPStan and Laravel Pint checks
- **Code Coverage**: Automated coverage reporting with Codecov

See [.github/workflows/test.yml](.github/workflows/test.yml) for the complete CI/CD configuration.

## Project Structure

```
.
├── backend/              # Laravel backend application
│   ├── app/             # Application code
│   ├── tests/           # Backend tests
│   └── ...
├── frontend/            # Vue 3 frontend application
│   ├── src/            # Source code
│   ├── e2e/            # E2E tests
│   └── ...
├── .github/            # GitHub Actions workflows
└── docs/               # Documentation
```

## Documentation

- [Architecture Overview](Architecture.md)
- [API Documentation](backend/README.md)
- [Login Credentials](LOGIN_CREDENTIALS.md)

## Contributing

Please read our contributing guidelines before submitting pull requests.

## License

This project is proprietary software. All rights reserved.

## Support

For support, please contact the development team or create an issue in the repository.
