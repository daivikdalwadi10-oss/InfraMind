@echo off
REM InfraMind Complete System Startup Script for Windows

setlocal enabledelayedexpansion

echo.
echo ========================================
echo   InfraMind - Complete System Startup
echo ========================================
echo.

REM Check Node.js
echo [1/5] Checking Node.js installation...
node --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Node.js not found. Please install Node.js first.
    exit /b 1
)
echo ✓ Node.js found
node --version

REM Check PHP
echo.
echo [2/5] Checking PHP installation...
php --version >nul 2>&1
if errorlevel 1 (
    echo ❌ PHP not found. Please install PHP first.
    exit /b 1
)
echo ✓ PHP found
php --version | findstr /R "^PHP"

REM Install Frontend Dependencies
echo.
echo [3/5] Installing frontend dependencies...
call npm --prefix frontend install
if errorlevel 1 (
    echo ❌ Failed to install frontend dependencies
    exit /b 1
)
echo ✓ Frontend dependencies installed

REM Setup Backend
echo.
echo [4/5] Setting up backend...
cd backend
if not exist database.sqlite (
    echo Creating database...
    php bin/migrate.php
    if errorlevel 1 (
        echo ❌ Database migration failed
        cd ..
        exit /b 1
    )
    
    echo Seeding database...
    php bin/seed.php
    if errorlevel 1 (
        echo ❌ Database seeding failed
        cd ..
        exit /b 1
    )
)
echo ✓ Backend ready
cd ..

REM Final Instructions
echo.
echo [5/5] System Ready
echo.
echo ✅ InfraMind is ready to start!
echo.
echo Next steps:
echo.
echo 1. Open Terminal 1 (Backend):
echo    cd backend
echo    php -S localhost:8000 -t public
echo.
echo 2. Open Terminal 2 (Frontend):
echo    cd frontend
echo    npm run dev
echo.
echo 3. Open your browser:
echo    Frontend: http://localhost:3000
echo    Database Admin: http://localhost:8000/admin-login.php
echo       (Login: admin / AdminPassword123!)
echo.
echo 4. Test Credentials:
echo    Owner:     owner@example.com / Owner123!@#
echo    Manager:   manager@example.com / Manager123!@#
echo    Employee1: employee1@example.com / Employee123!@#
echo    Employee2: employee2@example.com / Employee123!@#
echo.
pause
