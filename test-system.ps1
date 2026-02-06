#!/usr/bin/env pwsh
<#
.SYNOPSIS
    InfraMind Integration Test Script
    Tests all components of the system

.DESCRIPTION
    Validates that frontend, backend, and database are working correctly

.EXAMPLE
    .\test-system.ps1
#>

param(
    [switch]$SkipDependencies
)

Write-Host "`n========================================`n" -ForegroundColor Cyan
Write-Host "  InfraMind System Integration Test" -ForegroundColor Cyan
Write-Host "`n========================================`n" -ForegroundColor Cyan

$errors = @()
$warnings = @()
$success = @()

# ============================================================================
# SECTION 1: Check Prerequisites
# ============================================================================

Write-Host "[1/4] Checking Prerequisites..." -ForegroundColor Yellow

# Check Node.js
Write-Host "  ✓ Checking Node.js..." -ForegroundColor Gray
$nodeVersion = node --version 2>$null
if ($nodeVersion) {
    $success += "Node.js found: $nodeVersion"
    Write-Host "    ✅ $nodeVersion" -ForegroundColor Green
} else {
    $errors += "Node.js not found. Please install Node.js 18+"
    Write-Host "    ❌ Node.js not found" -ForegroundColor Red
}

# Check PHP
Write-Host "  ✓ Checking PHP..." -ForegroundColor Gray
$phpVersion = php --version 2>$null | Select-Object -First 1
if ($phpVersion) {
    $success += "PHP found: $phpVersion"
    Write-Host "    ✅ $phpVersion" -ForegroundColor Green
} else {
    $errors += "PHP not found. Please install PHP 8.2+"
    Write-Host "    ❌ PHP not found" -ForegroundColor Red
}

# Check Composer
Write-Host "  ✓ Checking Composer..." -ForegroundColor Gray
$composerVersion = composer --version 2>$null
if ($composerVersion) {
    $success += "Composer found"
    Write-Host "    ✅ Composer found" -ForegroundColor Green
} else {
    $warnings += "Composer not found. PHP dependencies may not be installed"
    Write-Host "    ⚠️  Composer not found" -ForegroundColor Yellow
}

# ============================================================================
# SECTION 2: Check Installation
# ============================================================================

Write-Host "`n[2/4] Checking Installation..." -ForegroundColor Yellow

# Check frontend node_modules
Write-Host "  ✓ Checking frontend dependencies..." -ForegroundColor Gray
if (Test-Path "c:\workspace\inframind\node_modules") {
    $success += "Frontend dependencies installed"
    Write-Host "    ✅ node_modules found" -ForegroundColor Green
} else {
    $warnings += "Frontend node_modules not found. Run 'npm install' in the root directory"
    Write-Host "    ⚠️  node_modules not found" -ForegroundColor Yellow
}

# Check backend vendor
Write-Host "  ✓ Checking backend dependencies..." -ForegroundColor Gray
if (Test-Path "c:\workspace\inframind\backend\vendor") {
    $success += "Backend dependencies installed"
    Write-Host "    ✅ vendor directory found" -ForegroundColor Green
} else {
    $warnings += "Backend vendor directory not found. Run 'composer install' in backend/"
    Write-Host "    ⚠️  vendor not found" -ForegroundColor Yellow
}

# Check database
Write-Host "  ✓ Checking database..." -ForegroundColor Gray
if (Test-Path "c:\workspace\inframind\backend\database.sqlite") {
    $size = (Get-Item "c:\workspace\inframind\backend\database.sqlite").Length
    $success += "Database found ($([math]::Round($size/1024, 1))KB)"
    Write-Host "    ✅ database.sqlite found ($([math]::Round($size/1024, 1))KB)" -ForegroundColor Green
} else {
    $warnings += "Database not found. Run 'php bin/migrate.php' in backend/"
    Write-Host "    ⚠️  database.sqlite not found" -ForegroundColor Yellow
}

# ============================================================================
# SECTION 3: Check Configuration
# ============================================================================

Write-Host "`n[3/4] Checking Configuration..." -ForegroundColor Yellow

# Check backend .env
Write-Host "  ✓ Checking backend .env..." -ForegroundColor Gray
if (Test-Path "c:\workspace\inframind\backend\.env") {
    $success += "Backend .env found"
    Write-Host "    ✅ backend/.env found" -ForegroundColor Green
    
    # Check DB_DRIVER
    $envContent = Get-Content "c:\workspace\inframind\backend\.env" | Select-String "DB_DRIVER"
    if ($envContent) {
        Write-Host "    ✅ DB_DRIVER configured" -ForegroundColor Green
    }
} else {
    $errors += "Backend .env not found"
    Write-Host "    ❌ backend/.env not found" -ForegroundColor Red
}

# Check frontend .env.local
Write-Host "  ✓ Checking frontend .env.local..." -ForegroundColor Gray
if (Test-Path "c:\workspace\inframind\.env.local") {
    $success += "Frontend .env.local found"
    Write-Host "    ✅ .env.local found" -ForegroundColor Green
} else {
    $warnings += "Frontend .env.local not found. Copy from .env.local.example"
    Write-Host "    ⚠️  .env.local not found (may use defaults)" -ForegroundColor Yellow
}

# Check next.config.js
Write-Host "  ✓ Checking Next.js config..." -ForegroundColor Gray
if (Test-Path "c:\workspace\inframind\next.config.js") {
    $success += "Next.js config found"
    Write-Host "    ✅ next.config.js found" -ForegroundColor Green
} else {
    $errors += "Next.js config missing"
    Write-Host "    ❌ next.config.js not found" -ForegroundColor Red
}

# ============================================================================
# SECTION 4: Code Quality
# ============================================================================

Write-Host "`n[4/4] Checking Code Quality..." -ForegroundColor Yellow

# Check TypeScript
Write-Host "  ✓ Checking TypeScript..." -ForegroundColor Gray
$tsOutput = & npm run typecheck -- --noEmit 2>&1 | Select-String -Pattern "error" | Measure-Object
if ($tsOutput.Count -eq 0) {
    $success += "TypeScript check passed"
    Write-Host "    ✅ No TypeScript errors" -ForegroundColor Green
} else {
    $warnings += "TypeScript errors found: $($tsOutput.Count) issue(s)"
    Write-Host "    ⚠️  TypeScript errors found" -ForegroundColor Yellow
}

# ============================================================================
# SUMMARY
# ============================================================================

Write-Host "`n========================================`n" -ForegroundColor Cyan
Write-Host "  Summary" -ForegroundColor Cyan
Write-Host "`n========================================`n" -ForegroundColor Cyan

if ($success) {
    Write-Host "✅ Success ($($success.Count)):" -ForegroundColor Green
    $success | ForEach-Object { Write-Host "   • $_" -ForegroundColor Green }
}

if ($warnings) {
    Write-Host "`n⚠️  Warnings ($($warnings.Count)):" -ForegroundColor Yellow
    $warnings | ForEach-Object { Write-Host "   • $_" -ForegroundColor Yellow }
}

if ($errors) {
    Write-Host "`n❌ Errors ($($errors.Count)):" -ForegroundColor Red
    $errors | ForEach-Object { Write-Host "   • $_" -ForegroundColor Red }
}

# ============================================================================
# RECOMMENDATIONS
# ============================================================================

if ($errors.Count -eq 0 -and $warnings.Count -eq 0) {
    Write-Host "`n✅ System is ready!" -ForegroundColor Green
    Write-Host "`nNext steps:" -ForegroundColor Cyan
    Write-Host "  1. Terminal 1: cd backend && php -S localhost:8000 -t public" -ForegroundColor Gray
    Write-Host "  2. Terminal 2: npm run dev" -ForegroundColor Gray
    Write-Host "  3. Browser:   http://localhost:3000" -ForegroundColor Gray
    Write-Host "`nDatabase access:" -ForegroundColor Cyan
    Write-Host "  • Simple viewer: http://localhost:8000/db-viewer.php" -ForegroundColor Gray
    Write-Host "  • Adminer:       http://localhost:8000/admin-login.php" -ForegroundColor Gray
} elseif ($errors.Count -gt 0) {
    Write-Host "`n❌ Fix the errors above before continuing" -ForegroundColor Red
    exit 1
} else {
    Write-Host "`n⚠️  Review warnings above" -ForegroundColor Yellow
}

Write-Host "`n"
