# 🚀 InfraMind Backend Migration - START HERE

Welcome to the new PHP backend! This guide will get you started in under 5 minutes.

## 📋 What's New?

The InfraMind backend has been completely migrated from Firebase to a professional PHP backend with:

- ✅ Secure JWT authentication
- ✅ MySQL/PostgreSQL relational database
- ✅ Complete audit trail & compliance logging
- ✅ Enterprise-grade security (OWASP Top 10)
- ✅ Strict analysis workflow enforcement
- ✅ Production-ready code

## ⚡ Quick Start (5 minutes)

### 1. Install Dependencies
```bash
cd backend
composer install
```

### 2. Setup Environment
```bash
cp .env.example .env
# Edit .env with your database credentials
```

### 3. Create Database
```bash
# MySQL
mysql -u root -p -e "CREATE DATABASE inframind CHARACTER SET utf8mb4;"

# PostgreSQL
psql -U postgres -c "CREATE DATABASE inframind;"
```

### 4. Run Migrations
```bash
php bin/migrate.php
```

### 5. Seed Test Data
```bash
php bin/seed.php
```

### 6. Start Server
```bash
composer start
# Server runs at http://localhost:8000
```

### 7. Test It
```bash
curl http://localhost:8000/health
# Should return: {"success": true, "data": {"status": "healthy"}}
```

## 🔑 Test Accounts

After seeding, configure local placeholders in backend/.env if you want to document credentials:

- DEV_OWNER_USER / DEV_OWNER_PASSWORD
- DEV_MANAGER_USER / DEV_MANAGER_PASSWORD
- DEV_EMPLOYEE_USER / DEV_EMPLOYEE_PASSWORD

## 📚 Documentation

| Document | Purpose |
|----------|---------|
| [MIGRATION_SUMMARY.md](./MIGRATION_SUMMARY.md) | Overview of what was built |
| [BACKEND_MIGRATION_GUIDE.md](./BACKEND_MIGRATION_GUIDE.md) | Complete API & technical reference |
| [FRONTEND_INTEGRATION.md](./FRONTEND_INTEGRATION.md) | How to update Next.js frontend |
| [DEPLOYMENT.md](./DEPLOYMENT.md) | Production deployment guide |
| [README.md](./README.md) | Project overview |

**Start with**: [MIGRATION_SUMMARY.md](./MIGRATION_SUMMARY.md)

## 🏗️ Project Structure

```
backend/
├── public/
│   └── index.php                    # Entry point
├── src/
│   ├── Core/                        # Database, config, auth, routing
│   ├── Controllers/                 # HTTP handlers
│   ├── Services/                    # Business logic
│   ├── Repositories/                # Data access
│   ├── Middleware/                  # Request processing
│   ├── Models/                      # Data models
│   ├── Validators/                  # Input validation
│   └── Exceptions/                  # Error classes
├── database/
│   ├── migrations/                  # SQL migrations
│   └── seeds/                       # Test data
├── bin/
│   ├── migrate.php                  # Migration runner
│   └── seed.php                     # Seed data
├── logs/                            # Application logs
├── composer.json                    # Dependencies
├── .env.example                     # Config template
└── [Documentation files]
```

## 🚀 Common Commands

```bash
# Development
composer start              # Start dev server on port 8000
composer test              # Run tests
composer lint              # Check code style
composer analyse           # Static analysis

# Database
php bin/migrate.php        # Run migrations
php bin/seed.php           # Seed test data

# Code quality
composer lint:fix          # Auto-fix code style
```

## 🔐 Security First

The entire backend is built with security in mind:

- **Passwords**: Bcrypt hashing (cost: 12)
- **Auth**: JWT tokens with 24-hour expiration
- **Database**: Prepared statements (zero SQL injection)
- **Validation**: All inputs validated
- **Logging**: Complete audit trail
- **CORS**: Protected against cross-origin attacks
- **Rate Limiting**: 100 requests/minute per IP
- **Errors**: Meaningful but not information-leaking

## 📖 API Examples

### Login
```bash
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "<DEV_MANAGER_USER>",
    "password": "<DEV_MANAGER_PASSWORD>"
  }'
```

Response:
```json
{
  "success": true,
  "data": {
    "accessToken": "eyJ...",
    "refreshToken": "eyJ...",
    "user": { "id": "uuid", "email": "...", "role": "MANAGER" }
  }
}
```

### Create Task (with token)
```bash
curl -X POST http://localhost:8000/tasks \
  -H "Authorization: Bearer <accessToken>" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Fix latency",
    "description": "Investigate and fix API latency",
    "assignedTo": "<employee-uuid>"
  }'
```

### Full API Reference
See [BACKEND_MIGRATION_GUIDE.md](./BACKEND_MIGRATION_GUIDE.md#api-endpoints)

## 🔄 Workflow Example

**Employee workflow:**
1. Create analysis from task
2. Add symptoms, signals, hypotheses
3. When readiness ≥ 75, submit
4. Manager reviews and approves/rejects

**Manager workflow:**
1. Create tasks, assign to employees
2. Review submitted analyses
3. Approve (creates report) or reject (request revisions)

**Owner workflow:**
1. View all finalized reports
2. Read-only access to analyses

See [BACKEND_MIGRATION_GUIDE.md#analysis-workflow](./BACKEND_MIGRATION_GUIDE.md#analysis-workflow) for details.

## 🆘 Troubleshooting

### Database Connection Error
```
Error: Failed to connect to database
```
✅ Check `.env` database credentials  
✅ Verify MySQL/PostgreSQL is running  
✅ Ensure database exists: `CREATE DATABASE inframind`

### JWT Token Invalid
```
Error: Invalid or expired token
```
✅ Check Authorization header format: `Bearer <token>`  
✅ Verify token not expired (24 hours)  
✅ Check JWT_SECRET in .env

### 404 Endpoint Not Found
```
Error: Not found
```
✅ Check endpoint path (case-sensitive)  
✅ Verify HTTP method (POST, GET, PUT)  
✅ Ensure trailing slashes are correct

### Full Troubleshooting Guide
See [BACKEND_MIGRATION_GUIDE.md#troubleshooting](./BACKEND_MIGRATION_GUIDE.md#troubleshooting)

## 🚀 Next Steps

### For Local Development
1. ✅ Run setup steps above
2. 📖 Read [BACKEND_MIGRATION_GUIDE.md](./BACKEND_MIGRATION_GUIDE.md)
3. 🧪 Test all endpoints with curl/Postman
4. 📝 Review code in `src/`

### For Frontend Updates
1. 📖 Read [FRONTEND_INTEGRATION.md](./FRONTEND_INTEGRATION.md)
2. 🔄 Update Next.js API calls
3. 🧪 Test end-to-end workflows
4. ✅ Deploy together

### For Production
1. 📖 Read [DEPLOYMENT.md](./DEPLOYMENT.md)
2. 🖥️ Configure web server
3. 🔐 Set secure environment variables
4. ⚙️ Run migrations on production DB
5. 📊 Set up monitoring

## 📞 Support

- 📖 **Documentation**: See links above
- 🐛 **Issues**: Check logs in `logs/app.log`
- 💾 **Database**: Check schema with your DB client
- 🔍 **Audit Trail**: Query `audit_logs` table

## ✨ Key Features at a Glance

| Feature | Details |
|---------|---------|
| **Authentication** | JWT tokens, secure refresh flow |
| **Authorization** | Role-based (EMPLOYEE, MANAGER, OWNER) |
| **Workflow** | State machine with validation |
| **Audit** | Complete change tracking |
| **Security** | Bcrypt, prepared statements, rate limiting |
| **Database** | MySQL 8.0+ / PostgreSQL 14+ |
| **API** | RESTful with consistent JSON responses |
| **Monitoring** | Health checks, structured logging |
| **Scaling** | Stateless, horizontally scalable |
| **Compliance** | GDPR-ready, audit-ready |

## 📊 Tech Stack

- **Language**: PHP 8.2+
- **Database**: MySQL 8.0+ / PostgreSQL 14+
- **Auth**: JWT (HS256)
- **Logging**: Monolog
- **Dependencies**: Minimal and well-curated

No heavy frameworks - just clean, professional PHP.

## 🎯 Architecture Highlights

- **Separation of Concerns**: Controllers → Services → Repositories
- **Middleware Pipeline**: Auth, CORS, rate limiting, logging
- **State Machine**: Strict workflow enforcement
- **Audit Trail**: Every operation logged
- **Error Handling**: Meaningful errors without leaking info
- **Validation**: Multi-layer input validation

## 💡 Code Examples

All code examples in the documentation use realistic scenarios:
- User authentication
- Task creation and assignment
- Analysis creation and workflow
- Manager reviews and decisions
- Report generation

## 🎓 Learning Path

1. **Start**: This file (you are here!)
2. **Understand**: [MIGRATION_SUMMARY.md](./MIGRATION_SUMMARY.md)
3. **Deep Dive**: [BACKEND_MIGRATION_GUIDE.md](./BACKEND_MIGRATION_GUIDE.md)
4. **Integrate**: [FRONTEND_INTEGRATION.md](./FRONTEND_INTEGRATION.md)
5. **Deploy**: [DEPLOYMENT.md](./DEPLOYMENT.md)

## ✅ Pre-Flight Checklist

Before going live:

- [ ] Database set up and tested
- [ ] All migrations run successfully
- [ ] Test data seeded (optional)
- [ ] Server running without errors
- [ ] All endpoints tested with curl/Postman
- [ ] Frontend integrated and tested
- [ ] Audit logs being written
- [ ] Monitoring configured
- [ ] Backups scheduled
- [ ] HTTPS configured (production)

## 🚁 Deployment Quick Link

```bash
# One-command setup (after cloning)
cd backend && \
cp .env.example .env && \
composer install && \
php bin/migrate.php && \
php bin/seed.php && \
composer start
```

---

**Ready to go!** 🚀

Start with the [MIGRATION_SUMMARY.md](./MIGRATION_SUMMARY.md) for the big picture, then dive into [BACKEND_MIGRATION_GUIDE.md](./BACKEND_MIGRATION_GUIDE.md) for all the details.

Questions? Check the documentation files or review the code - it's well-commented and organized.

**Happy coding!** 💻
