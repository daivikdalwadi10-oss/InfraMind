#!/usr/bin/env pwsh
# Complete API Integration Test Suite for InfraMind Backend

$BaseUrl = "http://localhost:8000"
$TestResults = @()

function Test-Endpoint {
    param(
        [string]$Name,
        [string]$Method = "GET",
        [string]$Path,
        [hashtable]$Body,
        [string]$Token
    )
    
    try {
        $Headers = @{
            "Content-Type" = "application/json"
        }
        
        if ($Token) {
            $Headers["Authorization"] = "Bearer $Token"
        }
        
        $Uri = "$BaseUrl$Path"
        
        if ($Method -eq "GET" -or $Method -eq "DELETE") {
            $Response = Invoke-RestMethod -Uri $Uri -Method $Method -Headers $Headers
        } else {
            $BodyJson = $Body | ConvertTo-Json
            $Response = Invoke-RestMethod -Uri $Uri -Method $Method -Headers $Headers -Body $BodyJson
        }
        
        $TestResults += @{
            Name = $Name
            Status = "PASS"
            StatusCode = 200
            Response = $Response
        }
        
        Write-Host "[PASS] $Name"
        return $Response
    }
    catch {
        $StatusCode = $_.Exception.Response.StatusCode.Value__
        $ErrorMessage = $_.Exception.Message
        
        # Try to get error details
        $ErrorBody = ""
        if ($_.Exception.Response) {
            try {
                $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
                $ErrorBody = $reader.ReadToEnd()
                $reader.Close()
            } catch {}
        }
        
        $TestResults += @{
            Name = $Name
            Status = "FAIL"
            StatusCode = $StatusCode
            Error = $ErrorMessage
            ErrorBody = $ErrorBody
        }
        
        Write-Host "[FAIL] $Name (Status: $StatusCode)"
        return $null
    }
}

Write-Host "`n========== InfraMind API Integration Tests ==========`n"

# Test 1: Health Check
Write-Host "Testing Health Endpoint..."
$HealthResponse = Test-Endpoint -Name "Health Check" -Method "GET" -Path "/health"

# Test 2: Login (Employee)
Write-Host "`nTesting Authentication..."
$LoginBody = @{
    email = "employee1@example.com"
    password = "password123ABC!"
}
$LoginResponse = Test-Endpoint -Name "Employee Login" -Method "POST" -Path "/auth/login" -Body $LoginBody

$EmployeeToken = $LoginResponse.data.accessToken
$EmployeeRefresh = $LoginResponse.data.refreshToken

# Test 3: Login (Manager)
$ManagerLoginBody = @{
    email = "manager@example.com"
    password = "password123ABC!"
}
$ManagerLoginResponse = Test-Endpoint -Name "Manager Login" -Method "POST" -Path "/auth/login" -Body $ManagerLoginBody

$ManagerToken = $ManagerLoginResponse.data.accessToken

# Test 4: Get Current User
Write-Host "`nTesting User Profile..."
$MeResponse = Test-Endpoint -Name "Get Current User (Employee)" -Method "GET" -Path "/auth/me" -Token $EmployeeToken

# Test 5: Task Creation (Manager)
Write-Host "`nTesting Task Management..."
$TaskBody = @{
    title = "Analyze System Performance"
    description = "Investigate current system performance metrics"
    assigned_to = "11ee2e7c-7251-46f1-a38b-5a6c9180d902"
}
$TaskResponse = Test-Endpoint -Name "Create Task (Manager)" -Method "POST" -Path "/tasks" -Body $TaskBody -Token $ManagerToken

$TaskId = $TaskResponse.data.id

# Test 6: Get Tasks
$TasksResponse = Test-Endpoint -Name "List Tasks" -Method "GET" -Path "/tasks" -Token $ManagerToken

# Test 7: Get Single Task
if ($TaskId) {
    $SingleTaskResponse = Test-Endpoint -Name "Get Single Task" -Method "GET" -Path "/tasks/$TaskId" -Token $ManagerToken
}

# Test 8: Create Analysis (Employee)
Write-Host "`nTesting Analysis Management..."
$AnalysisBody = @{
    task_id = $TaskId
    symptoms = "High CPU usage, memory leaks detected"
    signals = "CPU at 85%, RAM increasing gradually"
    analysis_type = "performance"
}
$AnalysisResponse = Test-Endpoint -Name "Create Analysis (Employee)" -Method "POST" -Path "/analyses" -Body $AnalysisBody -Token $EmployeeToken

$AnalysisId = $AnalysisResponse.data.id

# Test 9: Add Hypotheses to Analysis
if ($AnalysisId) {
    $HypothesesBody = @{
        hypotheses = @(
            @{
                text = "Memory leak in database driver"
                confidence = 85
                evidence = @("growing memory usage", "correlates with queries")
            },
            @{
                text = "Inefficient loop in cache management"
                confidence = 70
                evidence = @("high CPU peaks", "memory never released")
            }
        )
    }
    $HypothesesResponse = Test-Endpoint -Name "Add Hypotheses" -Method "POST" -Path "/analyses/$AnalysisId/hypotheses" -Body $HypothesesBody -Token $EmployeeToken
}

# Test 10: Update Analysis
if ($AnalysisId) {
    $UpdateAnalysisBody = @{
        symptoms = "Updated: High CPU usage, memory leaks confirmed"
        signals = "CPU at 88%, RAM increased by 2GB overnight"
    }
    $UpdateResponse = Test-Endpoint -Name "Update Analysis (Employee)" -Method "PUT" -Path "/analyses/$AnalysisId" -Body $UpdateAnalysisBody -Token $EmployeeToken
}

# Test 11: Get Analysis
if ($AnalysisId) {
    $GetAnalysisResponse = Test-Endpoint -Name "Get Analysis Details" -Method "GET" -Path "/analyses/$AnalysisId" -Token $EmployeeToken
}

# Test 12: Submit Analysis
if ($AnalysisId) {
    $SubmitBody = @{
        readiness_score = 85
    }
    $SubmitResponse = Test-Endpoint -Name "Submit Analysis (Employee)" -Method "POST" -Path "/analyses/$AnalysisId/submit" -Body $SubmitBody -Token $EmployeeToken
}

# Test 13: Manager Review Analysis
Write-Host "`nTesting Manager Review..."
if ($AnalysisId) {
    $ReviewBody = @{
        action = "approve"
        feedback = "Excellent analysis, well researched and documented"
    }
    $ReviewResponse = Test-Endpoint -Name "Manager Review/Approve Analysis" -Method "POST" -Path "/analyses/$AnalysisId/review" -Body $ReviewBody -Token $ManagerToken
}

# Test 14: Create Report
Write-Host "`nTesting Report Management..."
if ($AnalysisId) {
    $ReportBody = @{
        analysis_id = $AnalysisId
        executive_summary = "The system is experiencing critical memory management issues that require immediate attention. The database driver appears to have a memory leak."
    }
    $ReportResponse = Test-Endpoint -Name "Create Report" -Method "POST" -Path "/reports" -Body $ReportBody -Token $ManagerToken
}

# Test 15: Get Reports (Manager)
$ReportsResponse = Test-Endpoint -Name "List Reports (Manager)" -Method "GET" -Path "/reports" -Token $ManagerToken

# Test 16: Refresh Token
Write-Host "`nTesting Token Refresh..."
$RefreshBody = @{
    refresh_token = $EmployeeRefresh
}
$RefreshResponse = Test-Endpoint -Name "Refresh Access Token" -Method "POST" -Path "/auth/refresh" -Body $RefreshBody

# Test 17: Signup (New User)
Write-Host "`nTesting User Registration..."
$SignupBody = @{
    email = "newemployee@example.com"
    password = "SecurePassword123!"
    display_name = "New Employee"
    role = "EMPLOYEE"
}
$SignupResponse = Test-Endpoint -Name "Sign Up New User" -Method "POST" -Path "/auth/signup" -Body $SignupBody

# Summary Report
Write-Host "`n========== TEST SUMMARY ==========`n"

$PassCount = ($TestResults | Where-Object { $_.Status -eq "PASS" }).Count
$FailCount = ($TestResults | Where-Object { $_.Status -eq "FAIL" }).Count

Write-Host "Total Tests: $($TestResults.Count)"
Write-Host "Passed: $PassCount"
Write-Host "Failed: $FailCount"

if ($FailCount -gt 0) {
    Write-Host "`nFailed Tests:"
    $TestResults | Where-Object { $_.Status -eq "FAIL" } | ForEach-Object {
        Write-Host "  - $($_.Name) (Status: $($_.StatusCode))"
        if ($_.ErrorBody) {
            Write-Host "    Error: $($_.ErrorBody | Out-String | Select-Object -First 3)"
        }
    }
}

Write-Host "`nAPI Integration Tests Completed!"
