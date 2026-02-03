@echo off
REM Clear Adminer Cache and Restart
echo Clearing Adminer cache...

REM Kill PHP server
taskkill /F /IM php.exe >nul 2>&1

REM Wait for process to exit
timeout /t 2 /nobreak

REM Restart PHP server
echo Starting PHP server on port 8000...
cd /d "%~dp0\backend"
php -S 127.0.0.1:8000

pause
