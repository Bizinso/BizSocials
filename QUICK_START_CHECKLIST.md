# 🚀 BizSocials Quick Start Checklist

Follow these steps to get BizSocials running on your machine.

## ✅ Pre-Setup Checklist

- [ ] Docker Desktop is installed
- [ ] Docker Desktop is running (check the icon in your menu bar)
- [ ] You have at least 4GB RAM allocated to Docker
- [ ] Ports 8080, 3306, 6379, 7700, 8025, 9000, 9001 are available

## 🎯 One-Command Setup

Open your terminal in the BizSocials directory and run:

```bash
make setup
```

**OR** use the quick start script:

```bash
./quick-start.sh
```

That's it! Wait 3-5 minutes for everything to set up.

## ✅ Verify Setup

After setup completes, verify everything works:

### 1. Check Services Are Running

```bash
make ps
```

All services should show "Up" or "Up (healthy)"

### 2. Test API

Open in browser: http://localhost:8080

Or use curl:
```bash
curl http://localhost:8080/api/health
```

### 3. Run Tests

```bash
make test-unit
```

You should see tests passing! ✅

## 🎉 You're Ready!

Your BizSocials platform is now running. Here's what you can access:

| Service | URL | Purpose |
|---------|-----|---------|
| **API** | http://localhost:8080 | Main application |
| **MailHog** | http://localhost:8025 | View test emails |
| **MinIO** | http://localhost:9001 | S3 storage (admin/minioadmin) |
| **Meilisearch** | http://localhost:7700 | Search engine |

## 📝 Common Commands

```bash
# View logs
make logs

# Enter container shell
make shell

# Run all tests
make test

# Run specific test suites
make test-unit        # Unit tests
make test-feature     # Feature tests
make test-properties  # Property tests

# Stop services
make down

# Restart services
make restart

# View all commands
make help
```

## 🔧 Troubleshooting

### Port Already in Use

If you see "port already in use":

1. Check what's using it:
```bash
lsof -i :8080
```

2. Stop that service or change the port in `docker-compose.yml`

### Services Won't Start

1. Make sure Docker Desktop is running
2. Check logs:
```bash
make logs
```

3. Try restarting:
```bash
make down
make up
```

### Composer Install Fails

Your `.env` should have these Pusher values:
```
PUSHER_APP_ID=dummy
PUSHER_APP_KEY=dummy
PUSHER_APP_SECRET=dummy
```

Then run:
```bash
make setup
```

## 📚 Next Steps

1. ✅ Explore the API at http://localhost:8080
2. ✅ Check test emails at http://localhost:8025
3. ✅ Run the test suite: `make test`
4. ✅ Read [DOCKER_SETUP.md](DOCKER_SETUP.md) for detailed documentation
5. ✅ Start developing!

## 🆘 Need Help?

- View all commands: `make help`
- Check detailed docs: [DOCKER_SETUP.md](DOCKER_SETUP.md)
- View logs: `make logs`
- Enter container: `make shell`

## 🧹 Clean Slate

To completely reset everything:

```bash
make clean    # Stop and remove all data
make setup    # Fresh setup
```

---

**Happy coding! 🎉**
