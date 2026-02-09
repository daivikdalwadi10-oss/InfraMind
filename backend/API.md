# InfraMind API Documentation

## Base URL
`http://localhost:8000/api`

## Authentication
All protected endpoints require JWT token in the `Authorization` header:
```
Authorization: Bearer <accessToken>
```

## Endpoints

### Authentication

#### Login
- **POST** `/auth/login`
- **Body:**
  ```json
  {
    "email": "<DEV_EMPLOYEE_USER>",
    "password": "<DEV_EMPLOYEE_PASSWORD>"
  }
  ```
- **Response:** `{ success: true, data: { accessToken, refreshToken, user } }`

#### Sign Up
- **POST** `/auth/signup`
- **Body:**
  ```json
  {
    "email": "newuser@example.com",
    "password": "SecurePass123!",
    "displayName": "New User",
    "role": "EMPLOYEE"
  }
  ```
- **Response:** `{ success: true, data: { id, email, role, displayName, createdAt } }`

#### Get Current User
- **GET** `/auth/me`
- **Headers:** Requires Bearer token
- **Response:** `{ success: true, data: { user details } }`

#### Refresh Token
- **POST** `/auth/refresh`
- **Body:**
  ```json
  {
    "refreshToken": "<refreshToken>"
  }
  ```
- **Response:** `{ success: true, data: { accessToken, refreshToken } }`

### Health Check

#### System Health
- **GET** `/health`
- **Response:** `{ success: true, data: { status, timestamp } }`

### Tasks

#### Create Task
- **POST** `/tasks`
- **Headers:** Requires Bearer token (Manager role)
- **Body:**
  ```json
  {
    "title": "Task Title",
    "description": "Task Description",
    "assignedTo": "user-id"
  }
  ```
- **Response:** `{ success: true, data: { id, title, description, ... } }`

#### List Tasks
- **GET** `/tasks`
- **Headers:** Requires Bearer token
- **Query Params:** `?status=OPEN&limit=50&offset=0`
- **Response:** `{ success: true, data: [ { tasks } ] }`

#### Get Task
- **GET** `/tasks/{id}`
- **Headers:** Requires Bearer token
- **Response:** `{ success: true, data: { task details } }`

#### Update Task Status
- **PUT** `/tasks/{id}/status`
- **Headers:** Requires Bearer token (Manager role)
- **Body:** `{ "status": "COMPLETED" }`
- **Response:** `{ success: true, data: { updated task } }`

### Analyses

#### Create Analysis
- **POST** `/analyses`
- **Headers:** Requires Bearer token (Employee role)
- **Body:**
  ```json
  {
    "taskId": "task-id",
    "analysisType": "LATENCY"
  }
  ```
- **Response:** `{ success: true, data: { id, task_id, status: "DRAFT", ... } }`

#### Get Analysis
- **GET** `/analyses/{id}`
- **Headers:** Requires Bearer token
- **Response:** `{ success: true, data: { analysis details } }`

#### List Analyses
- **GET** `/analyses`
- **Headers:** Requires Bearer token
- **Query Params:** `?status=DRAFT&limit=50&offset=0`
- **Response:** `{ success: true, data: [ { analyses } ] }`

#### Update Analysis
- **PUT** `/analyses/{id}`
- **Headers:** Requires Bearer token (Employee, author only)
- **Body:**
  ```json
  {
    "symptoms": ["Updated symptoms"],
    "signals": ["Updated signals"],
    "readinessScore": 80
  }
  ```
- **Response:** `{ success: true, data: { updated analysis } }`

- **POST** `/analyses/{id}/submit`
- **Headers:** Requires Bearer token (Employee, author)
- **Body:** `{ "readinessScore": 85 }`
- **Response:** `{ success: true, data: { analysis with status: "SUBMITTED" } }`

#### Manager Review Analysis
- **POST** `/analyses/{id}/review`
- **Headers:** Requires Bearer token (Manager role)
- **Body:**
  ```json
  {
    "decision": "APPROVE" | "REJECT",
    "feedback": "Detailed feedback"
  }
  ```
- **Response:** `{ success: true, data: { analysis with updated status } }`

### Reports

#### Create Report
- **POST** `/reports`
- **Headers:** Requires Bearer token (Manager role)
- **Body:**
  ```json
  {
    "analysisId": "analysis-id",
    "executiveSummary": "Summary text",
    "rootCause": "Root cause",
    "impact": "Impact",
    "resolution": "Resolution",
    "preventionSteps": "Prevention steps"
  }
  ```
- **Response:** `{ success: true, data: { id, analysis_id, status: "DRAFT", ... } }`

#### Get Report
- **GET** `/reports/{id}`
- **Headers:** Requires Bearer token
- **Response:** `{ success: true, data: { report details } }`

#### List Reports
- **GET** `/reports`
- **Headers:** Requires Bearer token
- **Query Params:** `?status=FINALIZED&analysis_id=id`
- **Response:** `{ success: true, data: [ { reports } ] }`

#### Report With Analysis
- **GET** `/reports/{id}/full`

## Test Credentials

If you run `php bin/seed.php`, demo users are created for local testing. See the seed script for current emails and passwords.

## Access Control

- **Employees** can:
  - Create and manage their own analyses
  - Submit analyses for review
  - View assigned tasks

- **Managers** can:
  - Create and assign tasks
  - Review employee analyses
  - Create and finalize reports
  - View all submissions

- **Owners** can:
  - View finalized reports
  - Access system analytics
  - Manage user permissions

## Database Management

Access Adminer (web-based database manager) at:
```
http://localhost:8000/adminer.php
```

- **System:** SQLite
- **Database:** inframind
- **File:** C:\workspace\inframind\backend\database.sqlite
