# MySQL Migration Guide

## Current Status
✅ System is configured to work with **both SQLite and MySQL**
✅ Database manager available at: http://localhost:8000/db-manager.php (password: `admin123`)

## Option 1: Use SQLite (Current - No Installation Required)
The system is currently working with SQLite. No additional setup needed.

## Option 2: Migrate to MySQL

### Step 1: Install MySQL

**Option A: Install XAMPP (Recommended - Includes phpMyAdmin)**
1. Download XAMPP: https://www.apachefriends.org/download.html
2. Install XAMPP to `C:\xampp`
3. Start MySQL from XAMPP Control Panel
4. Access phpMyAdmin at: http://localhost/phpmyadmin

**Option B: Install MySQL Standalone**
1. Download MySQL Installer: https://dev.mysql.com/downloads/installer/
2. Run installer and choose "Server only" or "Full"
3. Set root password: `rootpassword`
4. Create database: `inframind`
5. Create user: `inframind` with password: `inframindpass`

### Step 2: Configure Database

Edit `backend/.env`:
```env
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=inframind
DB_USER=inframind
DB_PASSWORD=inframindpass
```

### Step 3: Create Database

```sql
CREATE DATABASE IF NOT EXISTS inframind CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON inframind.* TO 'inframind'@'localhost' IDENTIFIED BY 'inframindpass';
FLUSH PRIVILEGES;
```

### Step 4: Run Migration

```powershell
cd backend
php bin/migrate.php
php bin/seed.php
```

### Step 5: Access phpMyAdmin

**If using XAMPP:**
- URL: http://localhost/phpmyadmin
- Username: `inframind` or `root`
- Password: `inframindpass` or `rootpassword`

**If MySQL standalone:**
1. Download phpMyAdmin: https://www.phpmyadmin.net/downloads/
2. Extract to `C:\phpmyadmin`
3. Copy `config.sample.inc.php` to `config.inc.php`
4. Edit `config.inc.php`:
   ```php
   $cfg['Servers'][$i]['host'] = 'localhost';
   $cfg['Servers'][$i]['user'] = 'inframind';
   $cfg['Servers'][$i]['password'] = 'inframindpass';
   ```
5. Run: `php -S localhost:8080 -t C:\phpmyadmin`
6. Access: http://localhost:8080

## Built-in Database Manager

InfraMind includes a custom database manager that works with both SQLite and MySQL:

**URL**: http://localhost:8000/db-manager.php
**Password**: `admin123`

Features:
- View all tables
- Browse table data
- Row counts
- Works with current database (SQLite or MySQL)

## Verify Migration

```powershell
# Test backend
curl http://localhost:8000/api/health

# Test login
$json = '{"email":"manager@example.com","password":"Manager123!@#"}'
$bytes = [System.Text.Encoding]::UTF8.GetBytes($json)
$tempFile = New-TemporaryFile
[System.IO.File]::WriteAllBytes($tempFile.FullName, $bytes)
curl.exe -X POST -H "Content-Type: application/json" --data-binary "@$($tempFile.FullName)" http://localhost:8000/api/auth/login
Remove-Item $tempFile
```

## Troubleshooting

**MySQL connection fails:**
- Check MySQL is running: `Get-Service MySQL*` (PowerShell)
- Verify port 3306 is open: `netstat -an | findstr 3306`
- Check credentials in `.env` match MySQL users

**Migration fails:**
- Ensure database exists: `CREATE DATABASE inframind;`
- Check user permissions: `SHOW GRANTS FOR 'inframind'@'localhost';`
- Try with root user temporarily

**phpMyAdmin access denied:**
- Verify username/password in `config.inc.php`
- Check MySQL user exists: `SELECT User, Host FROM mysql.user;`
- Reset password: `ALTER USER 'inframind'@'localhost' IDENTIFIED BY 'inframindpass';`

## MySQL Schema

The MySQL schema includes:
- **ENUM types** for status fields (cleaner than CHECK constraints)
- **JSON columns** for flexible data (hypotheses, symptoms, signals)
- **ON UPDATE CASCADE** for automatic timestamp updates
- **Foreign keys with CASCADE** for referential integrity
- **Proper indexes** for performance

File: `backend/database/migrations/002_mysql_schema.sql`
