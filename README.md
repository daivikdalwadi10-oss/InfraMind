# InfraMind - Enterprise Infrastructure Analysis Platform

## 🎯 System Status: ✅ FULLY OPERATIONAL

Production-ready workflow system with strict IAM, state machine enforcement, and complete audit trails.

## 🏗️ Architecture

- **Frontend**: Next.js 16 (App Router) + TypeScript + Tailwind + shadcn/ui
- **Backend**: PHP 8.2+ REST API with MVC architecture
- **Database**: SQLite (production-ready for PostgreSQL/MySQL)
- **Auth**: JWT-based session authentication
- **AI**: Google Gemini 2.5-flash via Genkit wrapper

## 📁 Project Structure

```
/backend
  /bin                  → migrate.php, seed.php
  /database/migrations  → SQL schema
  /public               → index.php (entry point)
  /src
    /Controllers        → HTTP request handlers
    /Services           → Business logic + state machine
    /Repositories       → Data access layer
    /Middleware         → Auth, CORS, Logging, Rate Limit
    /Models             → Data models & enums
    /Validators         → Input validation
    /Core               → Database, Config, Logger, JWT

/src
  /app                  → Next.js routes + Server Actions
  /components           → React UI components
  /lib                  → Types, auth, API client
  /ai                   → Genkit AI flows
```

## 🚀 Quick Start

### 1. Backend Setup

```powershell
cd backend
composer install
cp .env.example .env  # Configure database path
php bin/migrate.php   # Create tables
php bin/seed.php      # Load test data
php -S localhost:8000 -t public router.php
```

### 2. Frontend Setup

```powershell
npm install
cp .env.local.example .env.local
# Set: NEXT_PUBLIC_API_URL=http://localhost:8000
# Set: GENKIT_API_KEY=<your-google-api-key>
npm run dev
```

### 3. Access

- **Frontend**: http://localhost:3000
- **Backend**: http://localhost:8000/api
- **Health**: http://localhost:8000/api/health

## 🔐 Test Credentials

| Role     | Email                  | Password        |
|----------|------------------------|-----------------|
| Employee | employee1@example.com  | Employee123!@#  |
| Manager  | manager@example.com    | Manager123!@#   |
| Owner    | owner@example.com      | Owner123!@#     |

## 🔄 Workflow State Machine

### Analysis Lifecycle (STRICT ENFORCEMENT)

```
DRAFT → SUBMITTED → APPROVED
  ↓         ↓
  ← NEEDS_CHANGES ←
```

### Valid Transitions

| From           | To              | Who      | Condition          |
|----------------|-----------------|----------|--------------------|
| DRAFT          | SUBMITTED       | Employee | Readiness ≥ 75%    |
| NEEDS_CHANGES  | SUBMITTED       | Employee | After revision     |
| SUBMITTED      | APPROVED        | Manager  | Review approved    |
| SUBMITTED      | NEEDS_CHANGES   | Manager  | Feedback provided  |

**All other transitions throw `InvalidStateException`**

## 🛡️ IAM & Authorization

### Backend = Source of Truth

- JWT validation on every API request
- Role checks in service layer
- State transition guards
- Audit logging on all actions

### Role Permissions Matrix

| Action               | EMPLOYEE | MANAGER | OWNER |
|----------------------|----------|---------|-------|
| Create analysis      | ✅       | ❌      | ❌    |
| Edit (DRAFT/NEEDS)   | ✅       | ❌      | ❌    |
| Submit analysis      | ✅       | ❌      | ❌    |
| Review analysis      | ❌       | ✅      | ❌    |
| Create tasks         | ❌       | ✅      | ❌    |
| Generate reports     | ❌       | ✅      | ❌    |
| View reports         | ❌       | ✅      | ✅    |

## 📊 Database Schema

### Core Tables
- `users` - Authentication & roles
- `tasks` - Work assignments
- `analyses` - Employee submissions (with symptoms, signals, hypotheses)
- `reports` - Manager-generated summaries

### Audit Tables
- `audit_logs` - All user actions
- `analysis_status_history` - State change tracking
- `analysis_revisions` - Version history

### Normalized
- `analysis_hypotheses` - Structured hypothesis storage

## 🤖 AI Integration (Server-Side Only)

### Wrapper (`src/ai/genkit.ts`)

```typescript
callGenkit({ model, prompt, maxTokens })
  → { success: boolean, data?: string, error?: string }
```

### Flows

- `suggestHypotheses` → `[{text, confidence, evidence}]`
- `draftExecutiveSummary` → `{summary, highlights, recommendedAction}`

### Rules

- All AI calls via Server Actions
- Responses must be valid JSON
- Parse errors throw exceptions
- No partial AI outputs to database

## 📡 API Endpoints

### Auth
- `POST /api/auth/signup` - Register
- `POST /api/auth/login` - Get tokens
- `POST /api/auth/refresh` - Refresh token
- `POST /api/auth/logout` - End session

### Tasks
- `GET /api/tasks` - List (role-filtered)
- `POST /api/tasks` - Create (manager)
- `GET /api/tasks/:id` - Details

### Analyses
- `GET /api/analyses` - List (role-filtered)
- `POST /api/analyses` - Create (employee)
- `PATCH /api/analyses/:id` - Update (employee, DRAFT/NEEDS_CHANGES)
- `POST /api/analyses/:id/submit` - Submit (employee, readiness ≥ 75)
- `POST /api/analyses/:id/review` - Approve/Reject (manager)

### Reports
- `GET /api/reports` - List (manager/owner)
- `POST /api/reports` - Generate (manager, from APPROVED)

## 🧪 Testing

```powershell
npm run test:unit           # Unit tests
npm run test:integration    # Integration (backend required)
npm run coverage            # Coverage report
```

## 🔧 Configuration

### Backend (.env)
```env
DB_DRIVER=sqlite
DB_PATH=./database.sqlite
JWT_SECRET=<64-char-minimum>
CORS_ALLOWED_ORIGINS=http://localhost:3000
LOG_LEVEL=debug
```

### Frontend (.env.local)
```env
NEXT_PUBLIC_API_URL=http://localhost:8000
GENKIT_API_KEY=<google-api-key>
```

## 🚨 Critical Rules

1. **Backend Authority**: Frontend NEVER decides permissions
2. **State Machine**: Invalid transitions rejected with error
3. **Readiness Gate**: Score ≥ 75 required for submission
4. **Audit Trail**: All changes logged
5. **Owner Isolation**: Reports visible only when FINALIZED
6. **AI JSON**: All AI outputs must parse as JSON

## 🐛 Troubleshooting

### Login Fails
```powershell
# Test backend
curl http://localhost:8000/api/health

# Verify credentials match test data
# Check browser DevTools Network tab
```

### Invalid State Transition
- Check current analysis status in database
- Verify user role matches permission requirement
- Review AnalysisService state machine logic

### Database Locked
- SQLite = single writer limitation
- Restart backend server
- Check for stale connections

## 📈 Production Checklist

- [ ] Migrate SQLite → PostgreSQL/MySQL
- [ ] Generate secure JWT_SECRET (64+ characters)
- [ ] Enable HTTPS
- [ ] Update CORS_ALLOWED_ORIGINS
- [ ] Configure database backups
- [ ] Set APP_ENV=production, APP_DEBUG=false
- [ ] Enable rate limiting in production
- [ ] Set up logging aggregation

## 📚 Documentation

- Backend API: [`backend/API.md`](backend/API.md)
- Setup Guide: [`backend/SETUP.md`](backend/SETUP.md)
- Deployment: [`backend/DEPLOYMENT.md`](backend/DEPLOYMENT.md)
- AI Agent: [`.github/copilot-instructions.md`](.github/copilot-instructions.md)

---

**Status**: ✅ Complete - IAM enforced, state machine validated, audit compliant
**Updated**: 2026-02-04
