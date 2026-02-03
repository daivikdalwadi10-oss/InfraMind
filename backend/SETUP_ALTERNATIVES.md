# Quick Backend Setup (No Docker Required)

Since Docker Desktop requires additional configuration, here's an alternative setup using Laragon or XAMPP for Windows development.

## Option 1: Laragon (Recommended - Easy Setup)

### Install Laragon
1. Download from https://laragon.org/download/
2. Install with defaults
3. Start Laragon

### Setup InfraMind Backend
```powershell
# Copy backend to Laragon's www directory
Copy-Item -Recurse C:\workspace\inframind\backend C:\laragon\www\inframind-backend

# Open Laragon terminal and run:
cd C:\laragon\www\inframind-backend
composer install
php bin/migrate.php
php bin/seed.php
php -S localhost:8000 -t public
```

### Access
- API: http://localhost:8000
- Database: localhost:3306 (root / no password)

## Option 2: XAMPP

### Install XAMPP
1. Download from https://www.apachefriends.org/
2. Install PHP 8.2+ version
3. Start Apache and MySQL from XAMPP Control Panel

### Setup InfraMind Backend
```powershell
# Copy backend to XAMPP htdocs
Copy-Item -Recurse C:\workspace\inframind\backend C:\xampp\htdocs\inframind-backend

# Open terminal and run:
cd C:\xampp\htdocs\inframind-backend
C:\xampp\php\php.exe composer install
C:\xampp\php\php.exe bin/migrate.php
C:\xampp\php\php.exe bin/seed.php
C:\xampp\php\php.exe -S localhost:8000 -t public
```

## Option 3: Standalone PHP (Manual)

### Install PHP 8.2+
```powershell
# Using Chocolatey
choco install php --version=8.2.0

# Or download manually from https://windows.php.net/
```

### Install Composer
```powershell
# Download and install from https://getcomposer.org/Composer-Setup.exe
```

### Install MySQL
```powershell
# Using Chocolatey
choco install mysql

# Or download from https://dev.mysql.com/downloads/mysql/
```

### Setup Backend
```powershell
cd C:\workspace\inframind\backend
composer install
php bin/migrate.php
php bin/seed.php
php -S localhost:8000 -t public
```

## Option 4: Fix Docker Desktop

Docker Desktop may need WSL2 enabled:

```powershell
# Run in Administrator PowerShell
wsl --install
wsl --set-default-version 2

# Restart computer
# Then start Docker Desktop again
```

## Test Accounts (After Setup)
- **Owner**: owner@example.com / password123ABC!
- **Manager**: manager@example.com / password123ABC!
- **Employee**: employee1@example.com / password123ABC!

## Health Check
```powershell
curl http://localhost:8000/health
```

## Next Steps

1. Choose one option above
2. Install the required tools
3. Run the setup commands
4. The backend will be available at http://localhost:8000

Let me know which option you prefer and I can guide you through it!
