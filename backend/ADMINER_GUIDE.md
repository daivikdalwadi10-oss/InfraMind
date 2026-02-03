# Adminer Access Guide

## Issue Resolution

**Error:** "No connection could be made because the target machine actively refused it"

**Solution:** The PHP server needs to be running.

---

## How to Access Adminer

### Step 1: Start the PHP Server

Open PowerShell and run:

```powershell
cd C:\workspace\inframind\backend
php -S localhost:8000 -t public
```

You should see:
```
Development Server started at http://localhost:8000
```

**Keep this terminal open** while using Adminer.

### Step 2: Open Adminer in Browser

Once the server is running, open your browser and go to:

```
http://localhost:8000/adminer.php
```

### Step 3: Connect to Database

**Database Selection Screen:**
- **System:** SQLite
- **Database:** (leave blank or type: `database.sqlite`)
- **Username:** (leave blank)
- **Password:** (leave blank)

Click **Login** (no credentials needed for SQLite)

---

## Troubleshooting

### Error: "Refused connection"
**Cause:** PHP server is not running
**Fix:** Run the start command in step 1 above

### Error: "Connection timeout"
**Cause:** Server is on wrong port or firewall blocking
**Fix:** 
- Check that the command shows `localhost:8000`
- Verify no other app is using port 8000
- Disable firewall temporarily to test

### Error: "Database file not found"
**Cause:** database.sqlite doesn't exist
**Fix:** Run `php setup-sqlite.php` from the backend directory

### Error: "Permission denied"
**Cause:** Insufficient file permissions
**Fix:** Ensure you have write access to the backend directory

---

## Quick Tips

### Reset Database
```powershell
php setup-sqlite.php
```

### Check Server Status
```powershell
# In another PowerShell window
Test-NetConnection -ComputerName localhost -Port 8000
```

### View Database File
```powershell
Get-Item database.sqlite | Select-Object FullName, Length, LastWriteTime
```

---

## Common Adminer Tasks

### View Tables
1. Login to Adminer
2. Click on table name in left sidebar
3. View all records with **Select Data** tab

### Run SQL Query
1. Click **SQL Command** tab
2. Type your SQL:
   ```sql
   SELECT * FROM users;
   ```
3. Click **Execute**

### View Specific Table
- Click table name in list to view structure
- Click **Select Data** to view records
- Click **Edit** to modify records

### Browse Database Structure
- Left sidebar shows all tables
- Click any table to see columns, indexes, and relationships

---

## Database Credentials for SQLite

```
System:     SQLite
Database:   database.sqlite (or leave blank)
User:       (none)
Password:   (none)
```

**No login required!** Just click Login.

---

## Accessing Adminer After Server Starts

| Device | URL |
|--------|-----|
| Same Computer | http://localhost:8000/adminer.php |
| Different Computer | http://[YOUR-IP]:8000/adminer.php |

Example: `http://192.168.1.100:8000/adminer.php`

---

## Important Notes

- **Development Only:** Adminer is unsecured (no authentication)
- **Keep Server Running:** Adminer won't work if PHP server stops
- **Database File:** Located at `C:\workspace\inframind\backend\database.sqlite`
- **No Credentials Needed:** SQLite doesn't require username/password

---

## Quick Start Command (Copy & Paste)

```powershell
cd C:\workspace\inframind\backend; php -S localhost:8000 -t public
```

Then open browser to:
```
http://localhost:8000/adminer.php
```

---

**Status:** ✅ Adminer fully functional  
**Server:** Running on port 8000  
**Access:** No authentication required
