# BizSocials Login Credentials

## Access URLs

- **User Login**: http://localhost:3000/login
- **Admin Login**: http://localhost:3000/admin/login
- **Backend API**: http://localhost:8080
- **Mailpit (Email Testing)**: http://localhost:8025
- **MinIO Console (File Storage)**: http://localhost:9001

## Super Admin Accounts (Platform Admin)

Use these at: http://localhost:3000/admin/login

| Email | Password | Role |
|-------|----------|------|
| admin@bizinso.com | BizS0c!als@2026! | Super Admin |
| support@bizinso.com | support@123 | Support Admin |
| viewer@bizinso.com | viewer@123 | Viewer Admin |

## Regular User Accounts (Tenant Users)

Use these at: http://localhost:3000/login

All users have password: **password**

### Acme Corporation (Enterprise)
- john.owner@acme.example.com - Owner
- jane.admin@acme.example.com - Admin
- bob.member@acme.example.com - Member
- eve.viewer@acme.example.com - Member

### StartupXYZ (Startup)
- sarah@startupxyz.example.com - Owner
- mike@startupxyz.example.com - Member

### Fashion Brand Co (B2C Brand)
- admin@fashionbrand.example.com - Owner

### John Freelancer (Individual)
- john@freelancer.example.com - Owner

### Sarah Lifestyle (Influencer)
- sarah@lifestyle.example.com - Owner (MFA enabled)

### Green Earth Foundation (Non-Profit)
- admin@greenearth.example.org - Owner
- volunteer@greenearth.example.org - Member

### Test Accounts
- user@pendingcorp.example.com - Pending status (not verified)
- user@suspendedinc.example.com - Suspended status

## Docker Services

All services are running via Docker:
- MySQL: localhost:3306
- Redis: localhost:6380
- MinIO: localhost:9000 (API), localhost:9001 (Console)
- Meilisearch: localhost:7700
- Mailpit: localhost:1025 (SMTP), localhost:8025 (Web UI)

## Notes

- The seeder ran partially but created all users successfully
- Some knowledge base categories already existed (duplicate error is safe to ignore)
- All regular users use the password: **password**
- Super admin default password can be changed via SUPER_ADMIN_PASSWORD env variable
