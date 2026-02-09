# Database Admin Access

## SQLite (Adminer)
- URL: http://localhost:8000/adminer.php
- No credentials required for SQLite
- Database file: backend/database.sqlite

## MySQL (phpMyAdmin)
- URL: http://localhost:8080
- Start MySQL + phpMyAdmin via `backend/docker-compose.yml`
- Database credentials match `backend/.env`
- MySQL is exposed on host port 3307 to avoid conflicts

## Backend Admin Login (optional)
- URL: http://localhost:8000/admin-login.php
- Redirects to phpMyAdmin after login
- Update credentials in backend/public/admin-login.php for production
