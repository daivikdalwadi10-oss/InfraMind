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

```
Email                      | Password         | Role
---------------------------|------------------|----------
owner@example.com         | password123ABC! | Owner
manager@example.com       | password123ABC! | Manager
employee1@example.com     | password123ABC! | Employee
employee2@example.com     | password123ABC! | Employee
```

## Quick API Tests

### 1. Health Check
```bash
curl http://localhost:8000/health
```

### 2. Login
```bash
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"employee1@example.com","password":"password123ABC!"}'
```

### 3. Create Task (Manager)
```bash
curl -X POST http://localhost:8000/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN_HERE" \
  -d '{
    "title":"New Task",
    "description":"Task description",
    "assigned_to":"11ee2e7c-7251-46f1-a38b-5a6c9180d902"
  }'
```

### 4. Create Analysis (Employee)
```bash
curl -X POST http://localhost:8000/analyses \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN_HERE" \
  -d '{
    "task_id":"TASK_ID",
    "symptoms":"High CPU usage",
    "signals":"CPU at 90%",
    "analysis_type":"performance"
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

✅ **22 REST API Endpoints**
- 5 Auth endpoints (login, signup, refresh, etc.)
- 5 Task endpoints (CRUD + status)
- 7 Analysis endpoints (CRUD + submit/review)
- 5 Report endpoints (CRUD + finalize)

✅ **Complete Database**
- 11 SQLite tables (users, tasks, analyses, reports, audit logs, etc.)
- 4 seeded test users
- Audit logging on all actions
- Soft delete support

✅ **Security Features**
- JWT authentication with access + refresh tokens
- Role-based access control (Employee, Manager, Owner)
- Rate limiting (10 req/min per IP)
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
5. **Production:** Migrate to PostgreSQL/MySQL, enable HTTPS

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
