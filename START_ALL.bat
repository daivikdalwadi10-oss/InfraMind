@echo off
REM InfraMind Quick Start Script (Windows)
REM This script starts both backend and frontend servers

echo.
echo ================================================================
echo           InfraMind - Complete Application Startup
echo ================================================================
echo.

REM Check if running in correct directory
if not exist "backend" (
    echo ERROR: Must run from c:\workspace\inframind directory
    echo Current directory: %CD%
    exit /b 1
)

echo Checking prerequisites...
echo.

REM Check for PHP
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: PHP is not installed or not in PATH
    echo Please install PHP and add it to your system PATH
    exit /b 1
)

echo ✓ PHP found: 
php --version | findstr "PHP"
echo.

REM Check for Node.js
where node >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: Node.js is not installed or not in PATH
    echo Please install Node.js from https://nodejs.org/
    exit /b 1
)

echo ✓ Node.js found:
node --version
echo.

echo ✓ npm found:
npm --version
echo.

REM Check if node_modules exist
if not exist "node_modules" (
    echo Installing frontend dependencies...
    call npm install
    if %errorlevel% neq 0 (
        echo ERROR: Failed to install dependencies
        exit /b 1
    )
    echo ✓ Dependencies installed
    echo.
)

REM Display instructions
echo ================================================================
echo                    STARTUP INSTRUCTIONS
echo ================================================================
echo.
echo Three terminal windows will be opened:
echo   1. Backend PHP Server (port 8000)
echo   2. Frontend Next.js Dev Server (port 3000)
echo   3. This control window
echo.
echo After servers start, open your browser:
echo   • Frontend:     http://localhost:3000
echo   • Backend API:  http://localhost:8000
echo   • Database:     http://localhost:8000/admin-login.php
echo.
echo Database Login:
echo   Username: admin
echo   Password: AdminPassword123!
echo.
echo Press any key to start servers...
pause >nul

REM Start Backend Server
echo.
echo Starting Backend PHP Server...
echo.
start "InfraMind - Backend (PHP)" /d "backend" php -S localhost:8000 -t public

REM Wait a moment for backend to start
timeout /t 2 /nobreak

REM Start Frontend Server
echo.
echo Starting Frontend Next.js Server...
echo.
start "InfraMind - Frontend (Next.js)" /d "." cmd /k "npm run dev"

echo.
echo ================================================================
echo                    SERVERS STARTED
echo ================================================================
echo.
echo Backend:  http://localhost:8000
echo Frontend: http://localhost:3000
echo Database: http://localhost:8000/admin-login.php
echo.
echo Press Ctrl+C in this window to continue (servers will keep running)
echo Use this window to view logs and information
echo.
echo To stop servers, close the terminal windows or press Ctrl+C in each
echo.

timeout /t 300 /nobreak

echo.
echo If you want to stop the servers, close their terminal windows
pause
