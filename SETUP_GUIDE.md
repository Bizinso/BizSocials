# 🚀 BizSocials Platform - Complete Setup Guide

**Last Updated:** February 14, 2026  
**Status:** ✅ Fully Configured and Ready

---

## 📋 Table of Contents

1. [Quick Start](#quick-start)
2. [What's Built](#whats-built)
3. [Running the Application](#running-the-application)
4. [Login Credentials](#login-credentials)
5. [Development Commands](#development-commands)
6. [Troubleshooting](#troubleshooting)
7. [Project Structure](#project-structure)

---

## 🎯 Quick Start

### Prerequisites
- Docker Desktop installed and running
- At least 4GB RAM allocated to Docker

### Start Everything (One Command)
```bash
make setup
```

This will:
1. Start all Docker services
2. Install dependencies
3. Run database migrations
4. Seed test data
5. Configure the application

**Time:** ~3-5 minutes

---

## 🌐 Running the Application

### Step 1: Start Backend (Docker)
```bash
make up
```

### Step 2: Start Frontend
```bash
cd frontend
npm run dev
```

### Step 3: Open in Browser
```
http://localhost:3000
```

### Step 4: Login
- **Email:** jane.admin@acme.example.com
- **Password:** password

---

## 🔑 Login Credentials

### Regular User Accounts
All accounts use password: **password**

#### Acme Corporation (Enterprise)
- john.owner@acme.example.com - Owner
- jane.admin@acme.example.com - Admin
- bob.member@acme.example.com - Member
- eve.viewer@acme.example.com - Viewer

#### Other Companies
- sarah@startupxyz.example.com - StartupXYZ Owner
- admin@fashionbrand.example.com - Fashion Brand Owner
- john@freelancer.example.com - Freelancer
- admin@greenearth.example.org - Non-Profit Owner

### Super Admin Accounts
Use at: http://localhost:3000/admin/login

- admin@bizinso.com / BizS0c!als@2026! - Super Admin
- support@bizinso.com / support@123 - Support Admin

---

## 🎨 What's Built

### ✅ Completed Features (300+ Tests)

#### Core Platform
- ✅ Authentication & Authorization
- ✅ Multi-tenant Workspace Management
- ✅ User Management with RBAC
- ✅ Role-based Permissions

#### Social Media Integrations
- ✅ Facebook (OAuth + API)
- ✅ Instagram (via Facebook Graph API)
- ✅ LinkedIn (OAuth + API)
- ✅ YouTube (OAuth + API)
- ⏳ Twitter/X (pending)
- ⏳ TikTok (pending)

#### Content Management
- ✅ Post Creation & Editing
- ✅ Post Scheduling System
- ✅ Multi-platform Publishing
- ✅ Content Calendar
- ✅ Media Library with Upload
- ✅ Image Optimization

#### WhatsApp Business
- ✅ API Client Implementation
- ✅ Message Sending/Receiving
- ✅ Webhook Handling
- ✅ Template Management
- ✅ Template Synchronization

#### Analytics & Reporting
- ✅ Data Collection from Platforms
- ✅ Analytics Aggregation
- ✅ Dashboard Metrics
- ✅ Real-time Statistics

#### Testing Infrastructure
- ✅ 300+ Test Files
- ✅ Unit Tests
- ✅ Feature/Integration Tests
- ✅ Property-Based Tests
- ✅ E2E Tests (Playwright)

### ⏳ In Progress
- Unified Inbox
- Approval Workflows
- Advanced Reporting
- Bulk Operations

---

## 🛠️ Development Commands

### Docker Commands
```bash
make up              # Start all services
make down            # Stop all services
make restart         # Restart services
make logs            # View logs
make ps              # Check service status
```

### Backend Commands
```bash
make shell           # Enter PHP container
make test-unit       # Run unit tests
make test-feature    # Run feature tests
make test-properties # Run property tests
make migrate         # Run migrations
make seed            # Seed database
```

### Frontend Commands
```bash
cd frontend
npm run dev          # Start dev server
npm run build        # Build for production
npm run test:e2e     # Run E2E tests
```

### Database Commands
```bash
make mysql           # Enter MySQL CLI
make fresh           # Fresh migrate with seeds
```

---

## 🌐 Access Points

| Service | URL | Purpose |
|---------|-----|---------|
| **Frontend** | http://localhost:3000 | Main application UI |
| **Backend API** | http://localhost:8080 | API endpoints |
| **MailHog** | http://localhost:8025 | View test emails |
| **MinIO** | http://localhost:9001 | S3 storage (minioadmin/minioadmin) |
| **Meilisearch** | http://localhost:7700 | Search engine |

---

## 🐛 Troubleshooting

### Frontend Shows Old Version
**Solution:** Hard refresh your browser
- **Mac:** `Cmd + Shift + R`
- **Windows:** `Ctrl + Shift + F5`

Or use Incognito mode for a fresh start.

### Login Returns 404 Error
**Check:** Browser DevTools (F12) → Network tab

Should see: `POST /api/v1/auth/login` with status `200`

If you see `/auth/login` (without `/api/v1`), clear browser cache.

### Docker Services Not Starting
```bash
# Check Docker is running
docker info

# Restart everything
make down
make up

# Check logs
make logs
```

### Port Already in Use
```bash
# Check what's using the port
lsof -i :3000  # or :8080

# Kill the process
lsof -ti:3000 | xargs kill -9
```

### Database Connection Issues
```bash
# Wait for MySQL to be ready (30-60 seconds on first start)
make ps

# Restart if needed
make restart
```

---

## 📁 Project Structure

```
BizSocials/
├── backend/                 # Laravel backend
│   ├── app/
│   │   ├── Http/Controllers/  # API endpoints
│   │   ├── Services/          # Business logic
│   │   ├── Models/            # Database models
│   │   └── Jobs/              # Queue jobs
│   ├── database/
│   │   ├── migrations/        # Database schema
│   │   ├── seeders/           # Test data
│   │   └── factories/         # Model factories
│   ├── tests/
│   │   ├── Unit/              # Unit tests
│   │   ├── Feature/           # Integration tests
│   │   └── Properties/        # Property-based tests
│   ├── .env                   # Environment config
│   └── Dockerfile             # PHP container
│
├── frontend/                # Vue.js frontend
│   ├── src/
│   │   ├── api/              # API client
│   │   ├── components/       # Vue components
│   │   ├── views/            # Pages
│   │   ├── stores/           # Pinia stores
│   │   └── router/           # Vue Router
│   ├── e2e/                  # E2E tests
│   ├── .env                  # Frontend config
│   └── vite.config.ts        # Vite config
│
├── docker/                  # Docker configurations
│   ├── nginx/               # Nginx config
│   ├── php/                 # PHP config
│   └── mysql/               # MySQL init
│
├── .kiro/specs/             # Feature specifications
│   └── platform-audit-and-testing/
│       ├── requirements.md
│       ├── design.md
│       └── tasks.md
│
├── docker-compose.yml       # Service definitions
├── Makefile                 # Development commands
└── SETUP_GUIDE.md          # This file
```

---

## 🔧 Configuration Files

### Backend Configuration
- `backend/.env` - Environment variables
- `backend/config/` - Laravel configuration
- `docker-compose.yml` - Docker services

### Frontend Configuration
- `frontend/.env` - API base URL and app config
- `frontend/vite.config.ts` - Vite and proxy settings
- `frontend/tailwind.config.js` - Tailwind CSS

### Key Environment Variables

#### Backend (`backend/.env`)
```env
APP_URL=http://localhost:8080
DB_HOST=mysql
REDIS_HOST=redis
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000
FRONTEND_URL=http://localhost:3000
```

#### Frontend (`frontend/.env`)
```env
VITE_API_BASE_URL=/api/v1
VITE_APP_URL=http://localhost:3000
```

---

## 📊 Testing

### Run All Tests
```bash
make test
```

### Run Specific Test Suites
```bash
make test-unit        # Unit tests
make test-feature     # Feature tests
make test-properties  # Property-based tests
```

### Run Tests with Coverage
```bash
make test-cov
```

### Run E2E Tests
```bash
cd frontend
npm run test:e2e
```

---

## 🚀 Deployment

### Build for Production

#### Backend
```bash
# Already containerized with Docker
docker compose up -d
```

#### Frontend
```bash
cd frontend
npm run build
# Output in frontend/dist/
```

---

## 📝 Development Workflow

### Daily Workflow
1. Start Docker: `make up`
2. Start frontend: `cd frontend && npm run dev`
3. Make changes
4. Run tests: `make test-unit`
5. Check logs: `make logs`
6. Stop when done: `make down`

### After Pulling Changes
```bash
make down
make up
make composer cmd="install"
make migrate
make cache-clear
make test
```

---

## 💡 Tips & Best Practices

1. **Keep Docker Running** - Leave Docker Desktop running in the background
2. **Use Make Commands** - All common tasks have make commands (`make help`)
3. **Check Logs Often** - `make logs` helps debug issues
4. **Test Frequently** - Run tests after making changes
5. **Use MailHog** - Check http://localhost:8025 to see test emails
6. **Hard Refresh** - Use `Cmd+Shift+R` when frontend doesn't update
7. **Incognito Mode** - Use for testing without cache issues

---

## 🆘 Getting Help

### Check System Status
```bash
./verify-setup.sh
```

### View All Commands
```bash
make help
```

### Check Service Health
```bash
make ps
docker compose ps
```

### View Logs
```bash
make logs              # All services
make logs-app          # App only
```

---

## 📞 Quick Reference

```bash
# Start everything
make up && cd frontend && npm run dev

# Stop everything
Ctrl+C (frontend) && make down

# Access application
http://localhost:3000

# Login
jane.admin@acme.example.com / password

# Run tests
make test-unit

# View logs
make logs

# Enter container
make shell
```

---

## ✅ Verification Checklist

After setup, verify:

- [ ] Docker containers running: `make ps`
- [ ] Backend responding: `curl http://localhost:8080/api`
- [ ] Frontend running: `curl http://localhost:3000`
- [ ] Can login at http://localhost:3000
- [ ] Tests passing: `make test-unit`
- [ ] MailHog accessible: http://localhost:8025

---

## 🎉 You're All Set!

Your BizSocials platform is ready for development!

**Start developing:**
```bash
make up
cd frontend && npm run dev
```

**Open:** http://localhost:3000

**Login:** jane.admin@acme.example.com / password

Happy coding! 🚀
