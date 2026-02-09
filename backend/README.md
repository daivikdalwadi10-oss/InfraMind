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

Server runs at http://localhost:8000

### Dev Server Notes

- `composer start` and `composer dev` run `php -S localhost:8000 -t public`
- If you need a custom router, use `php -S localhost:8000 -t public router.php`

## Architecture Highlights

- **Framework**: Custom lightweight PHP 8.2+ framework
- **Database**: SQLite by default, MySQL/PostgreSQL supported
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
composer install      # Install dependencies
composer start        # Start development server
composer dev          # Start development server (alias)
composer test         # Run tests
composer test:coverage # Generate HTML coverage report
composer lint         # Check code style
composer lint:fix     # Auto-fix code style
composer analyse      # Static analysis
php bin/migrate.php   # Run database migrations
php bin/seed.php      # Seed test data
```

## Test Accounts

After running `composer seed`, the database contains role-based users. Set local placeholders in backend/.env if you want to document credentials:

- DEV_OWNER_USER / DEV_OWNER_PASSWORD
- DEV_MANAGER_USER / DEV_MANAGER_PASSWORD
- DEV_EMPLOYEE_USER / DEV_EMPLOYEE_PASSWORD

## API Examples

### Login
```bash
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "<DEV_MANAGER_USER>",
    "password": "<DEV_MANAGER_PASSWORD>"
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
