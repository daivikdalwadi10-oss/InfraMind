# InfraMind Backend - PHP Migration Guide

## Overview

This is a complete backend migration from Firebase + Next.js to a PHP 8.2+ backend with MySQL/PostgreSQL. The system provides secure authentication, workflow enforcement, and enterprise-grade auditing.

## Architecture

```
backend/
├── public/index.php              # Single entry point (router)
├── src/
│   ├── Core/                     # Core services
│   │   ├── Database.php          # PDO connection & queries
│   │   ├── Config.php            # Environment configuration
│   │   ├── Logger.php            # Structured logging
│   │   ├── TokenManager.php      # JWT handling
│   │   ├── PasswordManager.php   # Secure password hashing
│   │   ├── Request.php           # HTTP request wrapper
│   │   ├── Response.php          # HTTP response builder
│   │   └── Router.php            # Route dispatcher
│   ├── Controllers/              # HTTP request handlers
│   │   ├── AuthController.php
│   │   ├── TaskController.php
│   │   ├── AnalysisController.php
│   │   ├── ReportController.php
│   │   └── HealthController.php
│   ├── Services/                 # Business logic
│   │   ├── AuthService.php       # User authentication
│   │   ├── TaskService.php       # Task management
│   │   ├── AnalysisService.php   # Analysis workflow
│   │   └── ReportService.php     # Report generation
│   ├── Repositories/             # Data access layer
│   │   ├── UserRepository.php
│   │   ├── TaskRepository.php
│   │   ├── AnalysisRepository.php
│   │   └── AuditLogRepository.php
│   ├── Middleware/               # HTTP middleware
│   │   └── Middleware.php        # Auth, CORS, rate limiting, logging
│   ├── Models/                   # Data models & enums
│   │   ├── Models.php
│   │   └── Enums.php
│   ├── Validators/               # Input validation
│   │   └── Validators.php
│   └── Exceptions/               # Custom exceptions
│       └── *.php
├── database/
│   ├── migrations/
│   │   └── 001_initial_schema.sql
│   └── seeds/
│       └── demo_data.php
├── bin/
│   ├── migrate.php              # Run database migrations
│   └── seed.php                 # Seed test data
├── logs/                        # Application logs
├── composer.json                # PHP dependencies
└── .env.example                 # Configuration template
```

## Setup & Installation

### 1. Prerequisites

- PHP 8.2 or higher
- MySQL 8.0+ or PostgreSQL 14+
- Composer

### 2. Installation

```bash
# Clone repository
git clone <repo> inframind-backend
cd inframind-backend

# Copy environment file
cp .env.example .env

# Install dependencies
composer install

# Run migrations
php bin/migrate.php

# Seed test data (optional)
php bin/seed.php

# Start development server
composer start
# Server runs on http://localhost:8000
```

### 3. Environment Configuration

Edit `.env`:

```env
# Database
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=inframind
DB_USER=root
DB_PASSWORD=

# JWT (change in production!)
JWT_SECRET=your-secret-key-change-in-production

# CORS
CORS_ORIGINS=http://localhost:3000,http://localhost:8000
```

## API Endpoints

### Authentication

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | /auth/signup | Register new user | No |
| POST | /auth/login | Login user | No |
| POST | /auth/refresh | Refresh access token | No |
| GET | /auth/me | Get current user | Yes |

### Tasks

| Method | Endpoint | Description | Auth | Role |
|--------|----------|-------------|------|------|
| POST | /tasks | Create task | Yes | MANAGER |
| GET | /tasks | List tasks | Yes | MANAGER, EMPLOYEE |
| GET | /tasks/:id | Get task | Yes | MANAGER, EMPLOYEE |
| PUT | /tasks/:id/status | Update status | Yes | MANAGER |

### Analyses

| Method | Endpoint | Description | Auth | Role |
|--------|----------|-------------|------|------|
| POST | /analyses | Create analysis | Yes | EMPLOYEE |
| GET | /analyses | List analyses | Yes | EMPLOYEE, MANAGER |
| GET | /analyses/:id | Get analysis | Yes | EMPLOYEE, MANAGER |
| PUT | /analyses/:id | Update content | Yes | EMPLOYEE |
| POST | /analyses/:id/submit | Submit analysis | Yes | EMPLOYEE |
| POST | /analyses/:id/review | Review analysis | Yes | MANAGER |

### Reports

| Method | Endpoint | Description | Auth | Role |
|--------|----------|-------------|------|------|
| POST | /reports | Create report | Yes | MANAGER |
| GET | /reports | List reports | Yes | MANAGER, OWNER |
| GET | /reports/:id | Get report | Yes | MANAGER, OWNER |
| GET | /reports/:id/full | Full report | Yes | MANAGER, OWNER |

## Authentication Flow

### Signup
```bash
POST /auth/signup
{
  "email": "user@example.com",
  "password": "SecurePass123!@#",
  "displayName": "John Doe",
  "role": "EMPLOYEE"
}
```

Response:
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "email": "user@example.com",
    "role": "EMPLOYEE",
    "displayName": "John Doe",
    "createdAt": "2024-02-02T10:00:00"
  }
}
```

### Login
```bash
POST /auth/login
{
  "email": "user@example.com",
  "password": "SecurePass123!@#"
}
```

Response:
```json
{
  "success": true,
  "data": {
    "accessToken": "eyJ...",
    "refreshToken": "eyJ...",
    "user": { ... }
  }
}
```

### Authenticated Requests
```bash
Authorization: Bearer <accessToken>
```

## Analysis Workflow

The analysis workflow is a strict state machine:

```
DRAFT
  ↓ (employee edits & increases readiness)
SUBMITTED (employee submits, readiness ≥ 75)
  ├→ APPROVED (manager approves)
  └→ NEEDS_CHANGES (manager rejects with feedback)
       ↓
     DRAFT (employee revises)
       ↓
     SUBMITTED
       └→ ...
```

### Key Rules

- **Creation**: Only employees can create analyses
- **Editing**: Only in DRAFT or NEEDS_CHANGES state
- **Submission**: Requires readiness score ≥ 75
- **Review**: Only managers can review SUBMITTED analyses
- **Reports**: Only created from APPROVED analyses
- **Audit**: All transitions logged with timestamps and user info

## Database Schema

### Core Tables

- **users**: User accounts with roles (EMPLOYEE, MANAGER, OWNER)
- **tasks**: Work items assigned by managers to employees
- **analyses**: Analysis documents with workflow state
- **reports**: Finalized reports from approved analyses

### Audit Tables

- **audit_logs**: Complete change history
- **analysis_status_history**: Analysis state transitions
- **analysis_revisions**: Version history of analysis content

All tables use UUID primary keys and timestamp tracking.

## Security Features

### 1. Authentication
- Bcrypt password hashing (cost: 12)
- JWT tokens with HS256 signature
- Access token expiration (24 hours)
- Refresh token rotation

### 2. Authorization
- Role-based access control (RBAC)
- Server-side permission validation
- Middleware-enforced auth checks
- Field-level access control

### 3. Data Protection
- Prepared statements (PDO)
- Input validation on all endpoints
- SQL injection prevention
- CORS enabled for trusted origins

### 4. Auditing
- Complete audit trail for all operations
- User and timestamp tracking
- Change history preservation
- Soft deletes for compliance

### 5. Rate Limiting
- Request rate limiting (100 req/60s)
- IP-based tracking
- Automatic cache cleanup

## Services & Business Logic

### AuthService
- User signup with role assignment
- Secure login with password verification
- JWT token generation & refresh
- Role-based authorization checks

### TaskService
- Create tasks (managers only)
- Assign to employees
- Status transitions (OPEN → IN_PROGRESS → COMPLETED)
- Task listing with filtering

### AnalysisService
- Create analysis from assigned task
- Update content (symptoms, signals, hypotheses)
- Readiness score calculation
- Strict workflow state enforcement
- Manager review (approve/reject)
- Revision tracking & audit logging

### ReportService
- Create reports from approved analyses
- Access control (owner can read all, managers own)
- Full analysis details in reports
- Pagination support

## Repositories (Data Access)

All data access goes through repositories using PDO prepared statements:

- **UserRepository**: User CRUD & auth queries
- **TaskRepository**: Task management queries
- **AnalysisRepository**: Analysis queries with normalization
- **AuditLogRepository**: Audit trail queries

## Testing

```bash
# Run unit tests
composer test

# Coverage report
composer test:coverage

# Lint code
composer lint

# Auto-fix lint issues
composer lint:fix

# Static analysis
composer analyse
```

## Logging

All application events are logged to `logs/app.log` with:

- Timestamp
- Log level (INFO, WARNING, ERROR, CRITICAL)
- Message & context
- Automatic daily rotation

## Performance Optimizations

- Connection pooling
- Query optimization with indices
- JSON field denormalization for hypotheses
- Pagination with limit/offset
- Efficient role-based queries

## Migration from Firebase

### Users
- Firebase Auth → Users table with password_hash
- Custom claims → Role column
- UID → UUID column

### Firestore Documents → Relational Tables
- `/analyses` → analyses table + normalized content
- `/tasks` → tasks table
- `/reports` → reports table

### Auth Flow
- Firebase tokens → JWT tokens
- Session cookies → Authorization header
- Real-time → REST endpoints

## Troubleshooting

### Database Connection Failed
- Verify `.env` credentials
- Check MySQL/PostgreSQL is running
- Ensure database exists

### JWT Token Invalid
- Check JWT_SECRET in .env
- Verify token not expired
- Ensure correct Authorization header format

### Permission Denied
- Verify user role matches endpoint requirements
- Check role in JWT token
- Confirm users table has correct role

### Rate Limiting Issues
- Check IP address recognition
- Clear rate limit cache in `logs/rate_limit/`
- Adjust RATE_LIMIT_* settings in `.env`

## Production Deployment

1. **Generate secure JWT secret**
   ```bash
   php -r 'echo bin2hex(random_bytes(32)) . "\n";'
   ```

2. **Set production environment variables**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   JWT_SECRET=<generated-secret>
   DB_PASSWORD=<strong-password>
   ```

3. **Run migrations**
   ```bash
   php bin/migrate.php
   ```

4. **Configure web server** (Nginx example)
   ```nginx
   location / {
       try_files $uri /public/index.php?$query_string;
   }
   ```

5. **Enable HTTPS** - All endpoints should run over TLS

6. **Monitor logs** - Set up log aggregation

7. **Regular backups** - Database backup strategy

## Support & Maintenance

- Review `logs/app.log` for issues
- Monitor audit trails for security events
- Regular security updates for dependencies
- Test migrations before deployment

---

**Backend Version**: 1.0.0  
**PHP Version**: 8.2+  
**Last Updated**: 2024-02-02
