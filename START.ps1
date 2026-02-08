# InfraMind System Startup Script
# Starts both backend (PHP) and frontend (Next.js) servers

Write-Host "`n=== InfraMind System Startup ===" -ForegroundColor Cyan

function Get-TestCredentials {
    param([string]$envPath)
    $result = @{}
    if (!(Test-Path $envPath)) {
        return $result
    }
    Get-Content $envPath | ForEach-Object {
        if ($_ -match '^\s*#') { return }
        if ($_ -match '^\s*([^=]+)\s*=\s*(.*)\s*$') {
            $key = $matches[1].Trim()
            $value = $matches[2]
            if ($value.StartsWith('"') -and $value.EndsWith('"')) {
                $value = $value.Trim('"')
            }
            if ($key -like 'TEST_*') {
                $result[$key] = $value
            }
        }
    }
    return $result
}

# Check if servers are already running
Write-Host "`n1. Checking for existing servers..." -ForegroundColor Yellow
$php = Get-Process | Where-Object {$_.ProcessName -eq 'php'} | Select-Object -First 1
$node = Get-Process | Where-Object {$_.ProcessName -eq 'node'} | Select-Object -First 1

if ($php -or $node) {
    Write-Host "   WARNING: Servers already running. Stopping them first..." -ForegroundColor Yellow
    Get-Process | Where-Object {$_.ProcessName -in @('php','node')} | Stop-Process -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 2
}

# Ensure Docker is available for MySQL/phpMyAdmin
Write-Host "`n2. Ensuring Docker is available..." -ForegroundColor Yellow
try {
    docker --version | Out-Null
} catch {
    Write-Host "   [ERROR] Docker is not available" -ForegroundColor Red
    exit 1
}
Write-Host "   [OK] Docker is available" -ForegroundColor Green

# Start MySQL and phpMyAdmin
Write-Host "`n3. Starting MySQL and phpMyAdmin..." -ForegroundColor Yellow
Push-Location "$PSScriptRoot\backend"
docker compose up -d mysql phpmyadmin
Pop-Location
Write-Host "   Waiting for MySQL to be ready..." -ForegroundColor Yellow
$mysqlReady = $false
for ($i = 0; $i -lt 12; $i++) {
    try {
        docker compose -f "$PSScriptRoot\backend\docker-compose.yml" exec -T mysql mysqladmin ping -h 127.0.0.1 -prootpassword | Out-Null
        $mysqlReady = $true
        break
    } catch {
        Start-Sleep -Seconds 3
    }
}
if (-not $mysqlReady) {
    Write-Host "   [ERROR] MySQL did not become ready" -ForegroundColor Red
    exit 1
}
Write-Host "   [OK] MySQL is ready" -ForegroundColor Green

# Run backend migrations and seed data
Write-Host "`n4. Running backend migrations and seed..." -ForegroundColor Yellow
Push-Location "$PSScriptRoot\backend"
if (!(Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Host "   [OK] Created backend .env from template" -ForegroundColor Green
}
php bin/migrate.php
if ($LASTEXITCODE -ne 0) {
    Write-Host "   [ERROR] Migration failed" -ForegroundColor Red
    Pop-Location
    exit 1
}
php bin/seed.php
if ($LASTEXITCODE -ne 0) {
    Write-Host "   [ERROR] Seeding failed" -ForegroundColor Red
    Pop-Location
    exit 1
}
Write-Host "   [OK] Database migrated/seeded" -ForegroundColor Green
Pop-Location

# Start backend server
Write-Host "`n5. Starting PHP backend server..." -ForegroundColor Yellow
$backendJob = Start-Job -ScriptBlock {
    Set-Location "$using:PSScriptRoot\backend"
    php -S localhost:8000 -t public router.php
}
Start-Sleep -Seconds 2

# Verify backend started
try {
    $health = Invoke-RestMethod -Uri "http://localhost:8000/api/health" -TimeoutSec 5
    if ($health.success) {
        Write-Host "   [OK] Backend running on http://localhost:8000" -ForegroundColor Green
    }
} catch {
    Write-Host "   [ERROR] Backend failed to start" -ForegroundColor Red
    Stop-Job $backendJob
    Remove-Job $backendJob
    exit 1
}

# Install frontend dependencies if missing
Write-Host "`n6. Ensuring frontend dependencies..." -ForegroundColor Yellow
Push-Location "$PSScriptRoot\frontend"
if (!(Test-Path "node_modules")) {
    npm install
}
Pop-Location

# Start frontend server
Write-Host "`n7. Starting Next.js frontend..." -ForegroundColor Yellow
$frontendJob = Start-Job -ScriptBlock {
    Set-Location "$using:PSScriptRoot\frontend"
    npm run dev
}
Start-Sleep -Seconds 5

# Prewarm frontend to avoid slow first load
Write-Host "`n8. Warming frontend..." -ForegroundColor Yellow
$frontendReady = $false
for ($i = 0; $i -lt 10; $i++) {
    try {
        Invoke-WebRequest -Uri "http://localhost:3000" -UseBasicParsing -TimeoutSec 10 | Out-Null
        $frontendReady = $true
        break
    } catch {
        Start-Sleep -Seconds 3
    }
}
if ($frontendReady) {
    Write-Host "   [OK] Frontend warmed" -ForegroundColor Green
} else {
    Write-Host "   [WARN] Frontend warmup timed out" -ForegroundColor Yellow
}

# Summary
Write-Host "`n=== Startup Complete ===" -ForegroundColor Cyan
Write-Host "`n[SUCCESS] System is running!" -ForegroundColor Green
Write-Host "`nAccess Points:" -ForegroundColor Cyan
Write-Host "  Frontend: http://localhost:3000" -ForegroundColor White
Write-Host "  Backend API: http://localhost:8000/api" -ForegroundColor White
Write-Host "  Health Check: http://localhost:8000/api/health" -ForegroundColor White
Write-Host "  DB Admin: http://localhost:8080" -ForegroundColor White

try {
    Invoke-WebRequest -Uri "http://localhost:3000" -UseBasicParsing -TimeoutSec 5 | Out-Null
    Write-Host "  Frontend Health: OK" -ForegroundColor Green
} catch {
    Write-Host "  Frontend Health: Not reachable yet" -ForegroundColor Yellow
}

Write-Host "`nTest Credentials:" -ForegroundColor Cyan
$testEnvPath = "$PSScriptRoot\backend\.env"
$testCreds = Get-TestCredentials $testEnvPath
if ($testCreds.Count -gt 0) {
    if ($testCreds['TEST_EMPLOYEE_EMAIL']) { Write-Host "  Employee: $($testCreds['TEST_EMPLOYEE_EMAIL'])" -ForegroundColor White }
    if ($testCreds['TEST_MANAGER_EMAIL']) { Write-Host "  Manager:  $($testCreds['TEST_MANAGER_EMAIL'])" -ForegroundColor White }
    if ($testCreds['TEST_OWNER_EMAIL']) { Write-Host "  Owner:    $($testCreds['TEST_OWNER_EMAIL'])" -ForegroundColor White }
    Write-Host "  Passwords are stored in backend/.env (not shown)." -ForegroundColor Gray
} else {
    Write-Host "  Configured in backend/.env (TEST_* variables)." -ForegroundColor White
}

Write-Host "`nServers:" -ForegroundColor Cyan
Write-Host "  Backend Job ID: $($backendJob.Id)" -ForegroundColor Gray
Write-Host "  Frontend Job ID: $($frontendJob.Id)" -ForegroundColor Gray

Write-Host "`nTo stop servers, run: Get-Job | Remove-Job -Force" -ForegroundColor Yellow
Write-Host "`nPress Ctrl+C to stop monitoring. Servers will continue in background.`n" -ForegroundColor Gray

# Keep monitoring (optional - user can Ctrl+C to exit without stopping servers)
try {
    while ($true) {
        Start-Sleep -Seconds 10
        $backendStatus = Get-Job -Id $backendJob.Id | Select-Object -ExpandProperty State
        $frontendStatus = Get-Job -Id $frontendJob.Id | Select-Object -ExpandProperty State
        if ($backendStatus -ne 'Running' -or $frontendStatus -ne 'Running') {
            Write-Host "`nWARNING: Server status changed:" -ForegroundColor Yellow
            Write-Host "  Backend: $backendStatus" -ForegroundColor Gray
            Write-Host "  Frontend: $frontendStatus" -ForegroundColor Gray
            break
        }
    }
} catch {
    # User pressed Ctrl+C - keep servers running
    Write-Host "`nMonitoring stopped. Servers continue in background." -ForegroundColor Yellow
}
