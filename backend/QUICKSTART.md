# InfraMind Backend - Quick Start

## Start the API Server (One Command)

```bash
cd C:\workspace\inframind\backend
php -S localhost:8000 -t public
```

Server ready at: **http://localhost:8000**

## Database Admin

Open in browser: **http://localhost:8000/adminer.php**
- Type: SQLite
- File: database.sqlite
- No authentication needed

## Test Credentials

If you run `php bin/seed.php`, demo users are created for local testing. See the seed script for current emails and passwords.

## Quick API Tests

### 1. Health Check
```bash
curl http://localhost:8000/api/health
```

### 2. Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"<DEV_EMPLOYEE_USER>","password":"<DEV_EMPLOYEE_PASSWORD>"}'
```

### 3. Create Task (Manager)
```bash
curl -X POST http://localhost:8000/api/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN_HERE" \
  -d '{
    "title":"New Task",
    "description":"Task description",
    "assignedTo":"11ee2e7c-7251-46f1-a38b-5a6c9180d902"
  }'
```

### 4. Create Analysis (Employee)
```bash
curl -X POST http://localhost:8000/api/analyses \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN_HERE" \
  -d '{
    "taskId":"TASK_ID",
    "analysisType":"LATENCY",
    "symptoms":["High CPU usage"],
    "signals":["CPU at 90%"]
  }'
```

## Directory Structure

```
backend/
├── public/
│   ├── index.php          # API entry point
│   └── adminer.php        # Database admin
├── src/
│   ├── Controllers/       # API endpoints
│   ├── Services/          # Business logic
│   ├── Repositories/      # Database access
│   ├── Middleware/        # Request middleware
│   └── Models/            # Data models
├── database.sqlite        # SQLite database
├── .env                   # Configuration
├── composer.json          # Dependencies
├── API.md                 # Full API documentation
└── SETUP.md              # Detailed setup guide
```

## What's Included

✅ **Core REST API Endpoints**
- Auth, tasks, analyses, reports, and health routes
- Additional resources for AI outputs, incidents, infrastructure state, risks, meetings, teams, and admin

✅ **Complete Database**
- SQLite tables for core workflow plus audit/history
- Optional seed data
- Audit logging on all actions
- Soft delete support

✅ **Security Features**
- JWT authentication with access + refresh tokens
- Role-based access control (Employee, Manager, Owner)
- Rate limiting (configurable; default 100 req/60s)
- CORS middleware
- Input validation on all endpoints

✅ **Developer Tools**
- Adminer web interface for database
- Error logging to file
- Debug mode for development
- PHP static analysis configured

## Next Steps

1. **Start Server:** `php -S localhost:8000 -t public`
2. **Explore DB:** Visit http://localhost:8000/adminer.php
3. **Test API:** Use curl/Postman with test credentials
4. **Frontend:** Connect Next.js frontend to this API
5. **Production:** Migrate to MySQL if needed, enable HTTPS

## Troubleshooting

**Port Already in Use:**
```bash
php -S localhost:8001 -t public  # Use different port
```

**Database Not Found:**
```bash
php setup-sqlite.php  # Recreate database and seed data
```

**Permission Denied:**
```bash
# Ensure current user has write access to directory
chmod 755 .
```

---

**API Documentation:** See [API.md](./API.md)
**Full Setup Guide:** See [SETUP.md](./SETUP.md)
