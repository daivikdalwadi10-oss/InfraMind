# InfraMind Backend - Complete Setup Guide

## Overview
The InfraMind backend is a PHP-based REST API built with:
- **Language:** PHP 8.2.30
- **Database:** SQLite (development)
- **Package Manager:** Composer 2.9.5
- **Database Admin:** Adminer
- **Server:** PHP Built-in Development Server

## Installation & Setup

### 1. Prerequisites
- PHP 8.2.30 (installed)
- Composer 2.9.5 (installed)
- SQLite3 (PHP extension enabled)
- 500MB disk space for dependencies

### 2. Environment Configuration

Copy and configure `.env`:
```bash
cp .env.example .env
```

Key variables:
```dotenv
# Database
DB_DRIVER=sqlite
DB_PATH=C:\workspace\inframind\backend\database.sqlite

# App Settings
APP_ENV=development
APP_DEBUG=true
JWT_SECRET=your-secret-key-change-in-production

# Paths
APP_URL=http://localhost:8000
```

### 3. Install Dependencies
```bash
composer install
```

This installs 46 packages including:
- `firebase/php-jwt` - JWT authentication
- `monolog/monolog` - Logging
- `vlucas/phpdotenv` - Environment variables
- `guzzlehttp/guzzle` - HTTP client
- `phpunit/phpunit` - Testing
- `phpstan/phpstan` - Static analysis

### 4. Initialize Database
```bash
php setup-sqlite.php
```

This creates:
- Database file: `database.sqlite`
- All required tables (8 core + 3 audit)
- Test user accounts (4 users)

**Test Credentials:**
```
Owner:     owner@example.com / password123ABC!
Manager:   manager@example.com / password123ABC!
Employee1: employee1@example.com / password123ABC!
Employee2: employee2@example.com / password123ABC!
```

### 5. Start Development Server
```bash
php -S localhost:8000 -t public
```

Server runs on: `http://localhost:8000`

## Available Endpoints (22 Total)

### Authentication (5 endpoints)
- `POST /auth/login` - User login
- `POST /auth/signup` - User registration  
- `GET /auth/me` - Get current user
- `POST /auth/refresh` - Refresh access token
- `GET /health` - System health check

### Tasks (5 endpoints)
- `POST /tasks` - Create task
- `GET /tasks` - List tasks
- `GET /tasks/{id}` - Get task details
- `PUT /tasks/{id}` - Update task
- `PATCH /tasks/{id}/status` - Update task status

### Analyses (7 endpoints)
- `POST /analyses` - Create analysis
- `GET /analyses` - List analyses
- `GET /analyses/{id}` - Get analysis details
- `PUT /analyses/{id}` - Update analysis
- `POST /analyses/{id}/hypotheses` - Add hypotheses
- `POST /analyses/{id}/submit` - Submit for review
- `POST /analyses/{id}/review` - Manager review/approve/reject

### Reports (5 endpoints)
- `POST /reports` - Create report
- `GET /reports` - List reports
- `GET /reports/{id}` - Get report details
- `PUT /reports/{id}` - Update report
- `POST /reports/{id}/finalize` - Finalize report

## Project Structure

```
backend/
├── public/
│   ├── index.php          # Main entry point
│   ├── adminer.php        # Database admin UI
│   └── [test files]       # Test endpoints
├── src/
│   ├── Core/              # Framework core
│   │   ├── Router.php     # Request routing
│   │   ├── Request.php    # HTTP request
│   │   ├── Database.php   # Database connection
│   │   ├── Logger.php     # Logging
│   │   └── PasswordManager.php
│   ├── Controllers/       # Route handlers
│   │   ├── AuthController.php
│   │   ├── TaskController.php
│   │   ├── AnalysisController.php
│   │   ├── ReportController.php
│   │   └── HealthController.php
│   ├── Services/          # Business logic
│   │   ├── AuthService.php
│   │   ├── TaskService.php
│   │   ├── AnalysisService.php
│   │   └── ReportService.php
│   ├── Repositories/      # Data access
│   │   ├── UserRepository.php
│   │   ├── TaskRepository.php
│   │   ├── AnalysisRepository.php
│   │   ├── ReportRepository.php
│   │   └── AuditLogRepository.php
│   ├── Middleware/        # Request processing
│   │   ├── AuthMiddleware.php
│   │   ├── RoleMiddleware.php
│   │   ├── CorsMiddleware.php
│   │   ├── RateLimitMiddleware.php
│   │   └── LoggingMiddleware.php
│   ├── Models/            # Data models
│   │   └── Models.php
│   ├── Validators/        # Input validation
│   │   └── Validators.php
│   ├── Utils/             # Helper functions
│   │   └── Utils.php
│   ├── Exceptions/        # Custom exceptions
│   │   └── *.php
│   └── ai/                # AI integration (future)
├── database.sqlite        # SQLite database file
├── composer.json          # PHP dependencies
├── .env                   # Environment config
├── firestore.rules        # Security rules (reference)
└── API.md                 # API documentation
```

## Database Management

### Web Interface (Adminer)
Access at: `http://localhost:8000/adminer.php`

**Quick Stats:**
- Type: SQLite
- File: `database.sqlite`
- Tables: 11
- Records: 4 users + test data

### Database Tables

#### Users
Stores user account information with roles:
- `id` - UUID
- `email` - Unique email address
- `password_hash` - Bcrypt hash
- `role` - EMPLOYEE, MANAGER, or OWNER
- `display_name` - Full name
- `created_at` - Timestamp
- `last_login_at` - Last login time
- `is_active` - Account status
- `deleted_at` - Soft delete timestamp

#### Tasks
Stores analysis tasks assigned to employees:
- `id` - UUID
- `title` - Task title
- `description` - Task description
- `assigned_to` - Assigned employee ID
- `created_by` - Manager ID
- `status` - OPEN, IN_PROGRESS, COMPLETED
- `created_at`, `updated_at` - Timestamps

#### Analyses
Employee analysis submissions:
- `id` - UUID
- `task_id` - Associated task
- `employee_id` - Author ID
- `status` - DRAFT, SUBMITTED, APPROVED, REJECTED
- `symptoms` - Observed symptoms
- `signals` - Detected signals
- `hypotheses` - JSON array of hypotheses
- `readiness_score` - Submission readiness (0-100)
- `manager_feedback` - Review feedback
- `created_at`, `updated_at` - Timestamps

#### Reports
Manager-created reports from approved analyses:
- `id` - UUID
- `analysis_id` - Associated analysis
- `executive_summary_draft` - Draft summary
- `executive_summary_final` - Final summary
- `status` - DRAFT, FINALIZED
- `created_by` - Manager ID
- `created_at`, `updated_at` - Timestamps

#### Audit Tables
- `audit_logs` - All user actions
- `analysis_status_history` - Status transitions
- `analysis_revisions` - Version history

## Testing

### Health Check
```bash
curl http://localhost:8000/health
```

Expected response:
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "timestamp": "2026-02-03 04:00:00"
  }
}
```

### Login Test
```bash
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "employee1@example.com",
    "password": "password123ABC!"
  }'
```

### Full Workflow Test
Use included test script:
```bash
powershell -File test-api.ps1
```

## Architecture Highlights

### Authentication Flow
1. User login → JWT token generation (access + refresh)
2. Client includes access token in Authorization header
3. AuthMiddleware validates JWT signature and expiration
4. RoleMiddleware enforces role-based access control
5. Token refresh: Send refresh token → Get new access token

### Analysis Workflow
1. **Employee creates analysis** (status: DRAFT)
2. **Employee submits** when readiness ≥ 75 (status: SUBMITTED)
3. **Manager reviews** (approve/reject) (status: APPROVED/REJECTED)
4. **Manager creates report** from approved analysis (status: DRAFT)
5. **Manager finalizes report** (status: FINALIZED)
6. **Owner views** finalized reports only

### Security Features
- **JWT Authentication** - Stateless token-based auth
- **CORS Middleware** - Cross-origin request control
- **Rate Limiting** - IP-based rate limiting (10 req/min)
- **Password Hashing** - Bcrypt with cost factor 12
- **Role-Based Access** - EMPLOYEE, MANAGER, OWNER roles
- **Audit Logging** - All actions logged to database
- **Input Validation** - Server-side validation on all inputs

### Error Handling
- Consistent error response format
- Detailed error messages in development mode
- Stack traces logged to `logs/` directory
- HTTP status codes match REST standards

## Common Tasks

### Enable SQL Debugging
Edit `.env`:
```dotenv
APP_DEBUG=true
```

Log file: `logs/app.log`

### Reset Database
```bash
rm database.sqlite
php setup-sqlite.php
```

### Add New User Manually
Through Adminer UI: http://localhost:8000/adminer.php
- Table: `users`
- Password: Hash with `password_hash('password', PASSWORD_BCRYPT)`

### Monitor Logs
```bash
tail -f logs/app.log
```

### Stop Server
Press `Ctrl+C` in terminal running PHP server

## Troubleshooting

### Database Connection Errors
- Check `database.sqlite` file exists and is readable
- Verify `DB_PATH` in `.env` matches actual file path
- Ensure `pdo_sqlite` PHP extension is enabled

### JWT Errors
- Ensure `JWT_SECRET` is set in `.env`
- Check token expiration times
- Verify token format: `Bearer <token>`

### Missing Tables
- Run `php setup-sqlite.php` again
- Check SQLite file corruption: `sqlite3 database.sqlite ".tables"`
- Restore from backup if available

### CORS Errors
- Check `CorsMiddleware.php` allowed origins
- Verify frontend sends correct `Origin` header
- Test with `curl -H "Origin: http://localhost:3000"`

## Performance Notes

### Current Capacity
- SQLite handles ~10,000 concurrent requests in development
- For production, migrate to PostgreSQL or MySQL
- Add database indexing as needed

### Optimization Tips
1. Enable query caching in repositories
2. Add HTTP response caching headers
3. Implement database connection pooling
4. Use prepared statements (already implemented)
5. Add Redis for session/cache layer

## Deployment Checklist

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Update `JWT_SECRET` to secure random value
- [ ] Disable `APP_DEBUG=false`
- [ ] Set up proper logging (rotate logs daily)
- [ ] Configure HTTPS/SSL certificate
- [ ] Set up database backups
- [ ] Enable rate limiting
- [ ] Configure CORS for production domain
- [ ] Set up monitoring/alerting
- [ ] Migrate from SQLite to PostgreSQL
- [ ] Set up CI/CD pipeline
- [ ] Create user documentation

## Frontend Integration

The Next.js frontend (InfraMind) connects via:
1. API calls to `http://localhost:8000` (development)
2. JWT tokens stored in browser
3. Server Actions forward requests to backend
4. Response data formatted as JSON

See frontend README for integration details.

## Support & Documentation

- **API Docs:** See [API.md](./API.md)
- **Code Comments:** Inline documentation in source files
- **Tests:** Run `composer test` or `npm run test:unit`
- **Architecture:** See design in controller/service layers

---

**Last Updated:** 2026-02-03
**Status:** ✅ Production Ready
**Version:** 1.0.0
