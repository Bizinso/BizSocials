# BizSocials — Digital Ocean Infrastructure

**Version:** 1.0
**Date:** February 2026
**Purpose:** Production infrastructure on Digital Ocean
**Author:** Solution Architecture

---

## 1. Infrastructure Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    DIGITAL OCEAN INFRASTRUCTURE                              │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                         LOAD BALANCER                                │   │
│  │                    (DO Load Balancer - $12/mo)                       │   │
│  └───────────────────────────────┬─────────────────────────────────────┘   │
│                                  │                                         │
│                                  ▼                                         │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                      APP PLATFORM / DROPLETS                         │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐               │   │
│  │  │   App Pod    │  │   App Pod    │  │   App Pod    │               │   │
│  │  │  (Laravel)   │  │  (Laravel)   │  │  (Laravel)   │               │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘               │   │
│  │                                                                      │   │
│  │  ┌──────────────┐  ┌──────────────┐                                 │   │
│  │  │Queue Worker  │  │  Scheduler   │                                 │   │
│  │  │  (Laravel)   │  │  (Laravel)   │                                 │   │
│  │  └──────────────┘  └──────────────┘                                 │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                  │                                         │
│         ┌────────────────────────┼────────────────────────┐               │
│         │                        │                        │               │
│         ▼                        ▼                        ▼               │
│  ┌──────────────┐        ┌──────────────┐        ┌──────────────┐        │
│  │  Managed DB  │        │   Managed    │        │    Spaces    │        │
│  │   (MySQL)    │        │    Redis     │        │  (S3-compat) │        │
│  │   $15/mo+    │        │   $15/mo+    │        │   $5/mo+     │        │
│  └──────────────┘        └──────────────┘        └──────────────┘        │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                      CDN (Spaces CDN)                                │   │
│  │                    Static assets delivery                            │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Digital Ocean Services Mapping

| Component | DO Service | Spec (Start) | Spec (Scale) |
|-----------|------------|--------------|--------------|
| **Web App** | App Platform / Droplets | 2 x Basic ($12/mo) | 4 x Premium ($48/mo) |
| **Database** | Managed MySQL | 1GB RAM ($15/mo) | 4GB RAM ($60/mo) |
| **Cache/Queue** | Managed Redis | 1GB RAM ($15/mo) | 2GB RAM ($30/mo) |
| **Object Storage** | Spaces | 250GB ($5/mo) | As needed |
| **CDN** | Spaces CDN | Included with Spaces | Included |
| **Load Balancer** | DO Load Balancer | Small ($12/mo) | Medium ($24/mo) |
| **DNS** | DO DNS | Free | Free |
| **SSL** | Let's Encrypt | Free | Free |
| **Monitoring** | DO Monitoring | Free | Free |
| **Alerts** | DO Alerts | Free | Free |

### Estimated Monthly Cost (Starting)

| Service | Cost |
|---------|------|
| 2x App Droplets (2GB) | $24 |
| 1x Worker Droplet (2GB) | $12 |
| Managed MySQL (1GB) | $15 |
| Managed Redis (1GB) | $15 |
| Spaces (250GB) | $5 |
| Load Balancer | $12 |
| **Total** | **~$83/month** |

---

## 3. Environment Setup

### 3.1 Environments

| Environment | Infrastructure | Purpose |
|-------------|----------------|---------|
| **Local** | Docker Compose | Developer machines |
| **Development** | 1 Droplet + Local DB | Integration testing |
| **Staging** | Mirrors production (smaller) | UAT, pre-release |
| **Production** | Full infrastructure | Live customers |

### 3.2 Region Selection

**Recommended:** `nyc1` or `sfo3` (US-based customers) or `fra1` (EU customers)

All resources in the same region for low latency.

---

## 4. Droplet Configuration

### 4.1 App Server Droplet

```yaml
# Droplet Spec
Image: Ubuntu 22.04 LTS
Size: s-2vcpu-2gb (Starting) → s-2vcpu-4gb (Scale)
Region: nyc1
VPC: bizsocials-vpc
Tags: [app, production]

# Software Stack
- PHP 8.3-FPM
- Nginx
- Supervisor (for queue workers)
- Node.js 20 (for asset building)
```

### 4.2 Cloud-Init Script (User Data)

```yaml
#cloud-config
package_update: true
package_upgrade: true

packages:
  - nginx
  - php8.3-fpm
  - php8.3-mysql
  - php8.3-redis
  - php8.3-mbstring
  - php8.3-xml
  - php8.3-bcmath
  - php8.3-gd
  - php8.3-curl
  - php8.3-zip
  - supervisor
  - certbot
  - python3-certbot-nginx

runcmd:
  - systemctl enable nginx
  - systemctl enable php8.3-fpm
  - systemctl enable supervisor
```

### 4.3 Nginx Configuration

```nginx
# /etc/nginx/sites-available/bizsocials
server {
    listen 80;
    server_name app.bizsocials.io;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name app.bizsocials.io;

    root /var/www/bizsocials/public;
    index index.php;

    # SSL (managed by Certbot)
    ssl_certificate /etc/letsencrypt/live/app.bizsocials.io/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.bizsocials.io/privkey.pem;

    # Security headers
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    # Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static file caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

---

## 5. Managed Database (MySQL)

### 5.1 Configuration

```yaml
Engine: MySQL 8
Version: 8.0.x
Size: db-s-1vcpu-1gb (Starting)
Region: nyc1
VPC: bizsocials-vpc
Standby: Enabled (Production)
Backups: Daily, 7-day retention
```

### 5.2 Connection Settings

```env
# .env (Production)
DB_CONNECTION=mysql
DB_HOST=db-mysql-nyc1-xxxxx-do-user-xxxxx-0.b.db.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=bizsocials
DB_USERNAME=bizsocials_app
DB_PASSWORD=${DB_PASSWORD}

# SSL required for managed DB
DB_SSL_MODE=REQUIRED
DB_SSL_CA=/etc/ssl/certs/ca-certificates.crt
```

### 5.3 Database Users

| User | Purpose | Permissions |
|------|---------|-------------|
| `doadmin` | Admin (DO managed) | ALL |
| `bizsocials_app` | Application | SELECT, INSERT, UPDATE, DELETE |
| `bizsocials_migrate` | Migrations | ALL on bizsocials.* |
| `bizsocials_readonly` | Reporting/Analytics | SELECT only |

---

## 6. Managed Redis

### 6.1 Configuration

```yaml
Version: Redis 7
Size: db-s-1vcpu-1gb
Region: nyc1
VPC: bizsocials-vpc
Eviction Policy: allkeys-lru
```

### 6.2 Connection Settings

```env
# .env (Production)
REDIS_HOST=db-redis-nyc1-xxxxx-do-user-xxxxx-0.b.db.ondigitalocean.com
REDIS_PASSWORD=${REDIS_PASSWORD}
REDIS_PORT=25061
REDIS_CLIENT=phpredis

# Queue
QUEUE_CONNECTION=redis

# Cache
CACHE_DRIVER=redis

# Session
SESSION_DRIVER=redis
```

---

## 7. Spaces (Object Storage)

### 7.1 Configuration

```yaml
Name: bizsocials-media
Region: nyc3
CDN: Enabled
Endpoint: https://bizsocials-media.nyc3.digitaloceanspaces.com
CDN Endpoint: https://bizsocials-media.nyc3.cdn.digitaloceanspaces.com
```

### 7.2 Laravel Filesystem Configuration

```php
// config/filesystems.php
'disks' => [
    'spaces' => [
        'driver' => 's3',
        'key' => env('DO_SPACES_KEY'),
        'secret' => env('DO_SPACES_SECRET'),
        'region' => env('DO_SPACES_REGION', 'nyc3'),
        'bucket' => env('DO_SPACES_BUCKET', 'bizsocials-media'),
        'endpoint' => env('DO_SPACES_ENDPOINT', 'https://nyc3.digitaloceanspaces.com'),
        'url' => env('DO_SPACES_CDN_URL', 'https://bizsocials-media.nyc3.cdn.digitaloceanspaces.com'),
        'visibility' => 'public',
        'throw' => true,
    ],
],
```

```env
# .env
DO_SPACES_KEY=your-spaces-key
DO_SPACES_SECRET=your-spaces-secret
DO_SPACES_REGION=nyc3
DO_SPACES_BUCKET=bizsocials-media
DO_SPACES_ENDPOINT=https://nyc3.digitaloceanspaces.com
DO_SPACES_CDN_URL=https://bizsocials-media.nyc3.cdn.digitaloceanspaces.com

FILESYSTEM_DISK=spaces
```

### 7.3 Bucket Structure

```
bizsocials-media/
├── posts/
│   └── {workspace_id}/
│       └── {post_id}/
│           ├── image_1.jpg
│           └── video_1.mp4
├── avatars/
│   └── {user_id}/
│       └── avatar.jpg
├── exports/
│   └── {workspace_id}/
│       └── {report_id}.pdf
└── temp/
    └── {uuid}/
        └── upload.tmp
```

---

## 8. Load Balancer

### 8.1 Configuration

```yaml
Name: bizsocials-lb
Region: nyc1
Algorithm: round_robin
Size: small

Health Checks:
  Protocol: HTTPS
  Port: 443
  Path: /health
  Interval: 10s
  Timeout: 5s
  Unhealthy Threshold: 3
  Healthy Threshold: 5

Forwarding Rules:
  - Entry: HTTPS:443 → HTTP:80 (Let's Encrypt at LB)

Sticky Sessions: Enabled (cookie-based)
SSL: Let's Encrypt (auto-renewal)
```

### 8.2 Health Check Endpoint

```php
// routes/api.php
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        Redis::ping();

        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
        ], 503);
    }
});
```

---

## 9. Deployment Strategy

### 9.1 Option A: App Platform (Recommended for Start)

```yaml
# .do/app.yaml
name: bizsocials
region: nyc
services:
  - name: api
    github:
      repo: your-org/bizsocials-backend
      branch: main
      deploy_on_push: true
    build_command: |
      composer install --no-dev --optimize-autoloader
      php artisan config:cache
      php artisan route:cache
      php artisan view:cache
    run_command: heroku-php-nginx -C nginx.conf public/
    envs:
      - key: APP_ENV
        value: production
      - key: APP_KEY
        type: SECRET
        value: ${APP_KEY}
      - key: DB_HOST
        value: ${db.HOSTNAME}
      - key: DB_PASSWORD
        type: SECRET
        value: ${db.PASSWORD}
    instance_count: 2
    instance_size_slug: basic-xs
    health_check:
      http_path: /health

  - name: worker
    github:
      repo: your-org/bizsocials-backend
      branch: main
    run_command: php artisan queue:work --tries=3 --timeout=90
    instance_count: 1
    instance_size_slug: basic-xs

  - name: scheduler
    github:
      repo: your-org/bizsocials-backend
      branch: main
    run_command: php artisan schedule:work
    instance_count: 1
    instance_size_slug: basic-xxs

  - name: frontend
    github:
      repo: your-org/bizsocials-frontend
      branch: main
    build_command: npm ci && npm run build
    environment_slug: node-js
    output_dir: dist
    instance_count: 1
    instance_size_slug: basic-xxs

databases:
  - name: db
    engine: MYSQL
    version: "8"
    size: db-s-1vcpu-1gb
    num_nodes: 1

  - name: redis
    engine: REDIS
    version: "7"
    size: db-s-1vcpu-1gb
    num_nodes: 1
```

### 9.2 Option B: Droplets with GitHub Actions

```yaml
# .github/workflows/deploy-production.yml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader

      - name: Build assets
        run: |
          npm ci
          npm run build

      - name: Deploy to Droplet
        uses: appleboy/ssh-action@v1.0.0
        with:
          host: ${{ secrets.DROPLET_IP }}
          username: deploy
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /var/www/bizsocials
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan queue:restart
            sudo systemctl reload php8.3-fpm
```

---

## 10. Monitoring & Alerts

### 10.1 Digital Ocean Monitoring

```yaml
# Built-in metrics (free)
- CPU utilization
- Memory usage
- Disk I/O
- Network bandwidth
- Load average

# Alert policies
alerts:
  - name: High CPU
    metric: cpu
    threshold: 80%
    duration: 5m
    channel: slack, email

  - name: High Memory
    metric: memory
    threshold: 85%
    duration: 5m
    channel: slack, email

  - name: Disk Space Low
    metric: disk
    threshold: 90%
    duration: 5m
    channel: slack, email

  - name: Droplet Down
    type: uptime
    channel: pagerduty, slack
```

### 10.2 Application Monitoring Stack

```
┌─────────────────────────────────────────────────────────────────┐
│                    MONITORING STACK                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Application Monitoring:                                        │
│  ├── Sentry (Error tracking) - Free tier or $26/mo             │
│  ├── Laravel Telescope (Dev only)                               │
│  └── Custom metrics via DO Agent                                │
│                                                                 │
│  Uptime Monitoring:                                             │
│  ├── DO Uptime Checks (Free)                                    │
│  └── Better Uptime / UptimeRobot (External)                     │
│                                                                 │
│  Log Management:                                                │
│  ├── Papertrail ($7/mo) or Logtail (Free tier)                 │
│  └── Laravel logging to external service                        │
│                                                                 │
│  APM (Optional):                                                │
│  └── New Relic / Scout APM ($25/mo)                            │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 10.3 Sentry Configuration

```env
# .env
SENTRY_LARAVEL_DSN=https://xxx@xxx.ingest.sentry.io/xxx
SENTRY_ENVIRONMENT=production
SENTRY_RELEASE=${APP_VERSION}
SENTRY_TRACES_SAMPLE_RATE=0.2
```

---

## 11. Backup Strategy

### 11.1 Database Backups

| Type | Frequency | Retention | Method |
|------|-----------|-----------|--------|
| Automated | Daily | 7 days | DO Managed (included) |
| Manual | Weekly | 30 days | mysqldump to Spaces |
| Pre-deploy | Before migrations | 24 hours | Automated in CI/CD |

### 11.2 Spaces Backup

```bash
# Sync to backup bucket (run via cron on separate droplet)
#!/bin/bash
s3cmd sync s3://bizsocials-media s3://bizsocials-backup/media/$(date +%Y-%m-%d)/
```

### 11.3 Disaster Recovery

| Scenario | RTO | RPO | Recovery Steps |
|----------|-----|-----|----------------|
| Droplet failure | 5 min | 0 | LB routes to healthy droplets |
| DB failure | 15 min | 24h | Restore from DO backup |
| Region outage | 1 hour | 24h | Failover to backup region |
| Data corruption | 30 min | 1h | Point-in-time recovery |

---

## 12. Security Configuration

### 12.1 VPC Network

```yaml
Name: bizsocials-vpc
Region: nyc1
IP Range: 10.116.0.0/20

# Resources in VPC
- All Droplets
- Managed Database
- Managed Redis
```

### 12.2 Firewall Rules

```yaml
# Cloud Firewall: bizsocials-fw
Inbound Rules:
  - Type: SSH
    Sources: Your IP / VPN only
    Port: 22

  - Type: HTTP
    Sources: Load Balancer only
    Port: 80

  - Type: HTTPS
    Sources: Load Balancer only
    Port: 443

Outbound Rules:
  - Type: All TCP
    Destinations: All

  - Type: All UDP
    Destinations: All

# Database Firewall (DO Managed)
Trusted Sources:
  - bizsocials-vpc
  - Your IP (for admin access)
```

### 12.3 SSH Hardening

```bash
# /etc/ssh/sshd_config
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
AllowUsers deploy
```

---

## 13. Cost Optimization Tips

| Strategy | Savings |
|----------|---------|
| Reserved Droplets (1-year) | 20% off |
| Right-size instances (start small) | Variable |
| Use Spaces CDN for static assets | Reduces bandwidth |
| Schedule dev/staging shutdown | ~50% on non-prod |
| Monitor and clean up unused resources | Variable |

---

## 14. Scaling Plan

### Phase 1: Launch (0-1000 users)

```
- 2x App Droplets (2GB)
- 1x Worker Droplet (1GB)
- Managed MySQL (1GB)
- Managed Redis (1GB)
- Spaces + CDN
Cost: ~$85/month
```

### Phase 2: Growth (1000-10000 users)

```
- 4x App Droplets (4GB)
- 2x Worker Droplets (2GB)
- Managed MySQL (4GB, standby)
- Managed Redis (2GB)
- Spaces + CDN
Cost: ~$250/month
```

### Phase 3: Scale (10000+ users)

```
- Kubernetes (DOKS) cluster
- Managed MySQL (8GB, 2 standby)
- Managed Redis cluster
- Multiple Spaces buckets
- Global CDN
Cost: ~$500+/month
```

---

## 15. Document Control

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | Feb 2026 | Solution Architecture | Initial DO infrastructure |

---

**END OF DIGITAL OCEAN INFRASTRUCTURE**
