# README - InfraMind Backend

A production-grade PHP backend for the InfraMind infrastructure analysis platform.

## Quick Start

```bash
# Setup
composer install
cp .env.example .env
php bin/migrate.php
php bin/seed.php

# Run
composer start
```

Server runs at `http://localhost:8000`

## Architecture Highlights

- **Framework**: Custom lightweight PHP 8.2+ framework
- **Database**: MySQL 8.0+ / PostgreSQL 14+ (normalized relational schema)
- **Authentication**: JWT-based with bcrypt password hashing
- **Authorization**: Role-based access control (RBAC)
- **Security**: Prepared statements, input validation, CORS, rate limiting
- **Audit**: Complete change tracking and compliance logging
- **State Machine**: Strict workflow enforcement for analyses

## Key Features

✅ Secure user authentication & JWT tokens  
✅ Role-based access control (EMPLOYEE, MANAGER, OWNER)  
✅ Analysis workflow state machine  
✅ Complete audit trail & revision history  
✅ Soft deletes for compliance  
✅ Rate limiting & DDoS protection  
✅ Structured logging with rotation  
✅ Input validation on all endpoints  
✅ CORS support  
✅ Health check endpoint  

## Project Structure

- `src/Core/` - Database, config, auth, request/response
- `src/Controllers/` - HTTP request handlers
- `src/Services/` - Business logic & workflows
- `src/Repositories/` - Data access layer
- `src/Middleware/` - Auth, CORS, rate limiting, logging
- `src/Models/` - Data models & enums
- `database/migrations/` - SQL migrations
- `bin/` - CLI tools (migrate, seed)
- `public/index.php` - Application entry point

## Documentation

See [BACKEND_MIGRATION_GUIDE.md](./BACKEND_MIGRATION_GUIDE.md) for complete documentation.

## Commands

```bash
npm install           # Install dependencies
npm run dev          # Start development server
npm run test         # Run tests
npm run lint         # Check code style
npm run migrate      # Run database migrations
npm run seed         # Seed test data
```

## Test Accounts

After running `npm run seed`:

- **Owner**: owner@example.com / Owner123!@#
- **Manager**: manager@example.com / Manager123!@#
- **Employee 1**: employee1@example.com / Employee123!@#
- **Employee 2**: employee2@example.com / Employee123!@#

## API Examples

### Login
```bash
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "manager@example.com",
    "password": "Manager123!@#"
  }'
```

### Create Analysis
```bash
curl -X POST http://localhost:8000/analyses \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "taskId": "uuid-here",
    "analysisType": "LATENCY"
  }'
```

## License

MIT
