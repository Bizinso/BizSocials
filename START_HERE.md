# 👋 START HERE - BizSocials Setup

Welcome! This guide will get you up and running in under 5 minutes.

## 🎯 The Fastest Way to Start

### Option 1: One Command (Recommended)

```bash
make setup
```

### Option 2: Quick Start Script

```bash
./quick-start.sh
```

Both do the same thing - set up everything automatically!

## ⏱️ What Happens During Setup?

1. ✅ Copies environment configuration
2. ✅ Starts Docker services (MySQL, Redis, etc.)
3. ✅ Installs PHP dependencies
4. ✅ Generates application key
5. ✅ Creates database tables
6. ✅ Seeds test data
7. ✅ Configures storage

**Time: 3-5 minutes**

## 🎉 After Setup

### Access Your Application

- **API**: http://localhost:8080
- **Email Testing**: http://localhost:8025
- **File Storage**: http://localhost:9001 (admin/minioadmin)

### Run Tests

```bash
make test-unit      # Quick unit tests
make test           # All tests
```

### View Logs

```bash
make logs
```

### Stop Services

```bash
make down
```

## 📚 Documentation

- **Quick Start**: [QUICK_START_CHECKLIST.md](QUICK_START_CHECKLIST.md) - Step-by-step checklist
- **Detailed Setup**: [DOCKER_SETUP.md](DOCKER_SETUP.md) - Complete Docker guide
- **All Commands**: Run `make help`

## ❓ Common Questions

**Q: Do I need to install PHP, MySQL, Redis, etc.?**  
A: No! Docker handles everything. Just have Docker Desktop running.

**Q: What if I get errors?**  
A: Check [DOCKER_SETUP.md](DOCKER_SETUP.md) troubleshooting section or run `make logs`

**Q: How do I stop everything?**  
A: Run `make down`

**Q: How do I start fresh?**  
A: Run `make clean` then `make setup`

## 🚀 Ready to Start?

Just run:

```bash
make setup
```

Then check out [QUICK_START_CHECKLIST.md](QUICK_START_CHECKLIST.md) to verify everything works!

---

**Need help?** Run `make help` to see all available commands.
