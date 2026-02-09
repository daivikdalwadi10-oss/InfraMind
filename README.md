# InfraMind - Enterprise Infrastructure Analysis Platform

InfraMind is a production-ready incident analysis platform with strict IAM, workflow state enforcement, and auditable decision trails across roles.

## System Architecture (High Level)

- **Frontend**: Next.js 16 (App Router) + TypeScript + Tailwind + shadcn/ui
- **Backend**: PHP 8.2+ REST API (MVC, service layer, repository pattern)
- **Database**: SQLite by default, with MySQL/PostgreSQL support
- **Auth**: JWT-based sessions and role enforcement (server-side)
- **AI**: Genkit flows calling Gemini 2.5 Flash (server-side only)

## Tech Stack

- Next.js, React 18, TypeScript, Tailwind CSS, shadcn/ui
- PHP 8.2+, Composer, PDO
- SQLite (default), MySQL/PostgreSQL (optional)
- Genkit + Gemini for structured AI output

## Project Structure

```
/backend
  /bin                  -> migrate.php, seed.php
  /database/migrations  -> SQL schema
  /public               -> index.php (entry point)
  /src
    /Controllers        -> HTTP request handlers
    /Services           -> Business logic + state machine
    /Repositories       -> Data access layer
    /Middleware         -> Auth, CORS, Logging, Rate Limit
    /Models             -> Data models & enums
    /Validators         -> Input validation
    /Core               -> Database, Config, Logger, JWT

/frontend
  /app                  -> Next.js routes
  /components           -> React UI components
  /lib                  -> Types, auth, API client
  /ai                   -> Genkit AI flows

/shared
  /README.md            -> Cross-cutting references (if needed)
```

## Roles and Permissions

- **EMPLOYEE**: create/edit analyses (DRAFT/NEEDS_CHANGES), submit when readiness >= 75
- **MANAGER**: review analyses, approve/reject, create tasks, generate reports
- **OWNER**: read-only access to finalized reports
- **DEVELOPER / SYSTEM_ADMIN**: admin console, maintenance, logs, feature flags

## Core Workflows

1. Task creation (manager)
2. Analysis creation (employee)
3. AI hypothesis generation (server-side, JSON only)
4. Submission gate (readiness >= 75)
5. Manager review (approve/reject)
6. Report generation (manager) and owner read-only access

## Quick Start (One Command)

```powershell
./START.ps1
```

This script checks prerequisites, configures environments, runs migrations, seeds data, and starts both servers.

## Manual Setup

### Backend

```powershell
cd backend
composer install
cp .env.example .env
php bin/migrate.php
php bin/seed.php
php -S localhost:8000 -t public router.php
```

### Frontend

```powershell
cd frontend
npm install
cp .env.local.example .env.local
npm run dev
```

## Environment Variables (No Secrets)

### Backend (.env)

- `APP_ENV`, `APP_DEBUG`, `APP_URL`, `SERVER_PORT`
- `DB_DRIVER` and `DB_PATH` (SQLite) or `DB_HOST`, `DB_NAME`, `DB_USER`
- `JWT_SECRET` (set in real environments)
- `CORS_ORIGINS`
- `GENKIT_API_KEY` (optional)
- `DEV_EMPLOYEE_USER`, `DEV_MANAGER_USER`, `DEV_OWNER_USER` (placeholders only)

### Frontend (.env.local)

- `NEXT_PUBLIC_API_URL`

## Local URLs

- Frontend: http://localhost:3000
- Backend API: http://localhost:8000/api
- Health: http://localhost:8000/api/health

## Deployment Notes

- Set `APP_ENV=production` and `APP_DEBUG=false`
- Provide a secure `JWT_SECRET` (64+ characters)
- Configure CORS for production origins
- Use MySQL/PostgreSQL for production workloads
- Enable HTTPS and centralized logging

## Documentation

- Backend API: backend/API.md
- Backend setup: backend/README.md
- Backend deployment: backend/DEPLOYMENT.md

## Validation Checklist (Final Pass)

- Auth + IAM enforced server-side
- Workflow transitions enforced and logged
- AI outputs parsed as JSON only
- Reports gated by approval and owner visibility
- No mock data or placeholder logic in production paths

## Hackathon Context

InfraMind was originally built for a rapid delivery context and has been hardened into a production-ready architecture with strict IAM, audit logging, and workflow enforcement.
- Setup Guide: [`backend/SETUP.md`](backend/SETUP.md)
- Deployment: [`backend/DEPLOYMENT.md`](backend/DEPLOYMENT.md)
- AI Agent: [`.github/copilot-instructions.md`](.github/copilot-instructions.md)

---

**Status**: ✅ Complete - IAM enforced, state machine validated, audit compliant
**Updated**: 2026-02-07
