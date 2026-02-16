# 🚀 BizSocials Platform

A comprehensive social media management platform for businesses, agencies, and individuals.

## ⚡ Quick Start

```bash
# One command setup
make setup

# Start frontend
cd frontend && npm run dev

# Open browser
http://localhost:3000
```

**Login:** jane.admin@acme.example.com / password

---

## 📚 Documentation

- **[SETUP_GUIDE.md](SETUP_GUIDE.md)** - Complete setup and usage guide
- **[DOCKER_SETUP.md](DOCKER_SETUP.md)** - Docker configuration details
- **[LOGIN_CREDENTIALS.md](LOGIN_CREDENTIALS.md)** - Test account credentials

---

## 🎯 What's Built

### ✅ Core Features
- Multi-tenant workspace management
- User authentication & authorization
- Role-based access control
- Social media integrations (Facebook, Instagram, LinkedIn, YouTube)
- Content creation & scheduling
- Media library
- WhatsApp Business integration
- Analytics dashboard
- 300+ automated tests

### 🛠️ Tech Stack
- **Backend:** Laravel 11, PHP 8.3, MySQL, Redis
- **Frontend:** Vue 3, TypeScript, Vite, PrimeVue, Tailwind
- **Infrastructure:** Docker, Nginx, MinIO, Meilisearch

---

## 🌐 Access Points

| Service | URL | Credentials |
|---------|-----|-------------|
| Frontend | http://localhost:3000 | See LOGIN_CREDENTIALS.md |
| Backend API | http://localhost:8080 | - |
| MailHog | http://localhost:8025 | - |
| MinIO | http://localhost:9001 | minioadmin/minioadmin |

---

## 🛠️ Common Commands

```bash
# Docker
make up              # Start services
make down            # Stop services
make logs            # View logs

# Backend
make test-unit       # Run unit tests
make shell           # Enter container
make migrate         # Run migrations

# Frontend
cd frontend
npm run dev          # Start dev server
npm run build        # Build for production
```

See `make help` for all available commands.

---

## 📁 Project Structure

```
BizSocials/
├── backend/         # Laravel backend
├── frontend/        # Vue.js frontend
├── docker/          # Docker configs
├── .kiro/specs/     # Feature specifications
└── docs/            # Additional documentation
```

---

## 🧪 Testing

```bash
make test-unit        # Unit tests
make test-feature     # Feature tests
make test-properties  # Property-based tests
```

---

## 📝 Development

1. Start Docker: `make up`
2. Start frontend: `cd frontend && npm run dev`
3. Open http://localhost:3000
4. Make changes and test
5. Run tests: `make test-unit`

---

## 🆘 Troubleshooting

See [SETUP_GUIDE.md](SETUP_GUIDE.md#troubleshooting) for common issues and solutions.

---

## 📄 License

Proprietary
