# CI/CD Setup Guide

This document explains how to configure the CI/CD pipeline for the BizSocials platform.

## Overview

The project uses GitHub Actions for continuous integration and deployment. The test suite includes:

- **Backend Unit Tests**: Fast tests using SQLite in-memory database
- **Backend Integration Tests**: Tests with MySQL service
- **E2E Tests**: Full browser testing with Playwright
- **Static Analysis**: PHPStan and Laravel Pint code quality checks
- **Code Coverage**: Automated coverage reporting with Codecov

## GitHub Actions Workflow

The main workflow is defined in `.github/workflows/test.yml` and runs on:
- Push to `main`, `master`, or `develop` branches
- Pull requests to `main`, `master`, or `develop` branches

## Required Secrets

To enable all features of the CI/CD pipeline, configure the following secrets in your GitHub repository:

### Code Coverage (Optional)

1. **CODECOV_TOKEN**: Token for uploading coverage reports to Codecov
   - Sign up at [codecov.io](https://codecov.io)
   - Add your repository
   - Copy the upload token
   - Add it as a GitHub secret

### Slack Notifications (Optional)

1. **SLACK_WEBHOOK_URL**: Webhook URL for Slack notifications
   - Go to your Slack workspace
   - Create a new Incoming Webhook app
   - Copy the webhook URL
   - Add it as a GitHub secret

### Email Notifications (Optional)

Configure these secrets for email notifications on test failures:

1. **MAIL_SERVER**: SMTP server address (e.g., `smtp.gmail.com`)
2. **MAIL_PORT**: SMTP port (e.g., `587` for TLS)
3. **MAIL_USERNAME**: SMTP username/email
4. **MAIL_PASSWORD**: SMTP password or app-specific password
5. **NOTIFICATION_EMAIL**: Email address to receive notifications

## Setting Up Secrets

1. Go to your GitHub repository
2. Navigate to **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Add each secret with its corresponding value

## Build Status Badges

Update the badges in `README.md` with your repository information:

```markdown
[![Test Suite](https://github.com/YOUR_USERNAME/YOUR_REPO/actions/workflows/test.yml/badge.svg)](https://github.com/YOUR_USERNAME/YOUR_REPO/actions/workflows/test.yml)
[![codecov](https://codecov.io/gh/YOUR_USERNAME/YOUR_REPO/branch/main/graph/badge.svg)](https://codecov.io/gh/YOUR_USERNAME/YOUR_REPO)
```

Replace `YOUR_USERNAME` and `YOUR_REPO` with your actual GitHub username and repository name.

## Workflow Jobs

### 1. Backend Unit Tests
- Runs on PHP 8.2
- Uses SQLite in-memory database
- Executes tests in parallel (4 processes)
- Fast execution (~2-5 minutes)

### 2. Backend Integration Tests
- Runs on PHP 8.2 with MySQL 8.0 service
- Tests API endpoints with real database
- Executes tests in parallel (4 processes)
- Moderate execution (~5-10 minutes)

### 3. E2E Tests
- Runs Playwright tests with Chromium, Firefox, and WebKit
- Tests complete user workflows
- Requires both backend and frontend servers
- Slower execution (~10-20 minutes)
- Uploads test reports and results as artifacts

### 4. Static Analysis
- Runs PHPStan at level 6
- Runs Laravel Pint for code style checks
- Fast execution (~1-2 minutes)

### 5. Code Coverage
- Runs tests with Xdebug coverage
- Generates coverage reports
- Uploads to Codecov (if configured)
- Enforces minimum 70% coverage
- Moderate execution (~5-10 minutes)

### 6. Notify
- Runs after all other jobs complete
- Sends Slack notification (if configured)
- Sends email on failure (if configured)
- Always runs, even if tests fail

## Local Testing

Before pushing, you can run tests locally to catch issues early:

### Backend Tests
```bash
cd backend

# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage --min=70
```

### Static Analysis
```bash
cd backend

# Run PHPStan
./vendor/bin/phpstan analyse

# Check code style
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint
```

### E2E Tests
```bash
cd frontend

# Run E2E tests
npm run test:e2e

# Run with UI
npm run test:e2e:ui

# Run in headed mode (see browser)
npm run test:e2e:headed
```

## Troubleshooting

### Tests Fail in CI but Pass Locally

1. **Database differences**: CI uses MySQL for integration tests, you might be using SQLite locally
2. **Environment variables**: Check that `.env.testing` is properly configured
3. **Dependencies**: Ensure `composer.lock` and `package-lock.json` are committed

### E2E Tests Timeout

1. Increase timeout in `frontend/e2e/playwright.config.ts`
2. Check that servers start properly in CI logs
3. Verify health check endpoints are working

### Coverage Upload Fails

1. Verify `CODECOV_TOKEN` is set correctly
2. Check that `coverage.xml` is generated
3. Review Codecov dashboard for errors

### Notifications Not Working

1. Verify webhook URLs and secrets are set
2. Check that the notify job has proper permissions
3. Review GitHub Actions logs for error messages

## Performance Optimization

### Caching
The workflow caches:
- Composer dependencies (backend)
- npm dependencies (frontend)
- Playwright browsers

This significantly speeds up subsequent runs.

### Parallel Execution
Tests run in parallel where possible:
- Unit tests: 4 processes
- Integration tests: 4 processes
- Multiple jobs run concurrently

### Conditional Execution
Some jobs only run when needed:
- Email notifications only on main branch failures
- Slack notifications only if webhook is configured

## Best Practices

1. **Keep tests fast**: Unit tests should complete in seconds
2. **Use appropriate test types**: Don't use E2E tests for unit-testable logic
3. **Monitor coverage**: Aim for 80%+ coverage on critical paths
4. **Fix flaky tests**: Investigate and fix tests that fail intermittently
5. **Review static analysis**: Address PHPStan warnings promptly
6. **Update dependencies**: Keep testing tools up to date

## Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Pest PHP Documentation](https://pestphp.com)
- [Playwright Documentation](https://playwright.dev)
- [PHPStan Documentation](https://phpstan.org)
- [Codecov Documentation](https://docs.codecov.com)
