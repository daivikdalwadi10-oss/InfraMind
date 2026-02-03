# InfraMind - Quick Start Guide

## ✅ All Services Are Running!

### 🌐 Access URLs

| Service | URL | Description |
|---------|-----|-------------|
| **Frontend** | http://localhost:3000 | Next.js application (login, dashboards) |
| **Login Page** | http://localhost:3000/login | User authentication |
| **API Backend** | http://localhost:8000 | PHP REST API |
| **Health Check** | http://localhost:8000/health | API status endpoint |
| **Database Admin** | http://localhost:8000/adminer.php | SQLite database browser |

---

## 🔐 Test Credentials

Use these credentials to log in:

- **Employee:** employee1@example.com / password123ABC!
- **Manager:** manager@example.com / password123ABC!
- **Owner:** owner@example.com / password123ABC!

---

## 🚀 How to Start (if servers stop)

### Start PHP Backend
```powershell
cd C:\workspace\inframind\backend
php -S localhost:8000 -t public
```

### Start Next.js Frontend
```powershell
cd C:\workspace\inframind
npm run dev
```

---

## ✨ What's Working

✅ **PHP Backend** - All 22 REST API endpoints operational
✅ **Next.js Frontend** - Updated to use PHP authentication
✅ **Login/Signup** - Fully integrated with PHP backend
✅ **Database** - SQLite with 4 test users
✅ **Session Management** - Cookie-based authentication
✅ **Role-Based Access** - Employee, Manager, Owner dashboards

---

## 🧪 Test the System

1. **Open Frontend:** http://localhost:3000
2. **Click "Sign in"** to go to login page
3. **Enter credentials:** employee1@example.com / password123ABC!
4. **You'll be redirected** to the Employee Dashboard
5. **Try different roles** by logging in with manager@ or owner@ accounts

---

## 📊 API Endpoints (accessible via http://localhost:8000)

### Authentication
- `POST /auth/login` - Login with email/password
- `POST /auth/signup` - Create new account
- `POST /auth/refresh` - Refresh access token
- `GET /auth/me` - Get current user profile

### Tasks
- `POST /tasks` - Create task (manager only)
- `GET /tasks` - List tasks
- `GET /tasks/{id}` - Get single task
- `PUT /tasks/{id}/status` - Update task status

### Analyses
- `POST /analyses` - Create analysis
- `GET /analyses` - List analyses
- `GET /analyses/{id}` - Get analysis
- `PUT /analyses/{id}` - Update analysis
- `POST /analyses/{id}/submit` - Submit for review
- `POST /analyses/{id}/review` - Manager review

### Reports
- `POST /reports` - Create report
- `GET /reports` - List reports
- `GET /reports/{id}` - Get report
- `GET /reports/{id}/full` - Get full report

### System
- `GET /health` - Health check

---

## 🛠️ Troubleshooting

### If PHP server stops:
```powershell
cd C:\workspace\inframind\backend
php -S localhost:8000 -t public
```

### If Next.js stops:
```powershell
cd C:\workspace\inframind
npm run dev
```

### Check if services are running:
```powershell
netstat -ano | Select-String ':(8000|3000)'
```

### View database:
Open http://localhost:8000/adminer.php
- Server: (leave blank for SQLite)
- Database: Click "Browse" to view data

---

## 📝 Project Structure

```
inframind/
├── backend/              # PHP REST API
│   ├── public/          # Web-accessible files
│   │   ├── index.php    # Main API entry point
│   │   └── adminer.php  # Database admin
│   ├── src/             # PHP source code
│   └── database.sqlite  # SQLite database
│
└── src/                 # Next.js frontend
    ├── app/            # Next.js App Router
    │   ├── (auth)/     # Login/Signup pages
    │   ├── (authenticated)/ # Protected pages
    │   └── actions.ts  # Server Actions (PHP API calls)
    ├── lib/
    │   ├── api.ts      # PHP API client
    │   └── auth.ts     # Session management
    └── components/     # React components
```

---

## 🎯 Next Steps

1. **Test Login Flow** - Use the test credentials
2. **Explore Dashboards** - Try Employee, Manager, Owner roles
3. **Create Tasks** - Login as manager, create a task
4. **Start Analysis** - Login as employee, create analysis from task
5. **Review & Approve** - Login as manager, review submission
6. **View Reports** - Login as owner, view finalized reports

---

## ✅ Everything is Connected!

- Frontend calls PHP backend via `src/lib/api.ts`
- Authentication uses session cookies (no Firebase!)
- All data stored in SQLite database
- Role-based access enforced on both frontend & backend

**You're ready to use InfraMind!** 🎉
