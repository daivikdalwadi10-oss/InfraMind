# Database Admin Access

## MySQL (phpMyAdmin)
- URL: http://localhost:8080
- Start MySQL + phpMyAdmin via `backend/docker-compose.yml`
- Database credentials match `backend/.env`
 - MySQL is exposed on host port 3307 to avoid conflicts

## Backend Admin Login (optional)
- URL: http://localhost:8000/admin-login.php
- Redirects to phpMyAdmin after login
