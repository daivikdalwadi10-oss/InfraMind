# InfraMind API Documentation

## Base URL
`http://localhost:8000`

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
    "email": "employee1@example.com",
    "password": "password123ABC!"
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
    "assigned_to": "user-id"
  }
  ```
- **Response:** `{ success: true, data: { id, title, description, ... } }`

#### List Tasks
- **GET** `/tasks`
- **Headers:** Requires Bearer token
- **Query Params:** `?status=OPEN&assigned_to=user-id&created_by=user-id`
- **Response:** `{ success: true, data: [ { tasks } ] }`

#### Get Task
- **GET** `/tasks/{id}`
- **Headers:** Requires Bearer token
- **Response:** `{ success: true, data: { task details } }`

#### Update Task
- **PUT** `/tasks/{id}`
- **Headers:** Requires Bearer token (Manager role)
- **Body:** Any task fields to update
- **Response:** `{ success: true, data: { updated task } }`

#### Update Task Status
- **PATCH** `/tasks/{id}/status`
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
    "task_id": "task-id",
    "symptoms": "Description of symptoms",
    "signals": "Observed signals",
    "analysis_type": "performance"
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
- **Query Params:** `?status=DRAFT&employee_id=user-id`
- **Response:** `{ success: true, data: [ { analyses } ] }`

#### Update Analysis
- **PUT** `/analyses/{id}`
- **Headers:** Requires Bearer token (Employee, author only)
- **Body:**
  ```json
  {
    "symptoms": "Updated symptoms",
    "signals": "Updated signals"
  }
  ```
- **Response:** `{ success: true, data: { updated analysis } }`

#### Add Hypotheses
- **POST** `/analyses/{id}/hypotheses`
- **Headers:** Requires Bearer token (Employee, author)
- **Body:**
  ```json
  {
    "hypotheses": [
      {
        "text": "Hypothesis text",
        "confidence": 80,
        "evidence": ["evidence 1", "evidence 2"]
      }
    ]
  }
  ```
- **Response:** `{ success: true, data: { updated analysis } }`

#### Submit Analysis
- **POST** `/analyses/{id}/submit`
- **Headers:** Requires Bearer token (Employee, author)
- **Body:** `{ "readiness_score": 85 }`
- **Response:** `{ success: true, data: { analysis with status: "SUBMITTED" } }`

#### Manager Review Analysis
- **POST** `/analyses/{id}/review`
- **Headers:** Requires Bearer token (Manager role)
- **Body:**
  ```json
  {
    "action": "approve" | "reject",
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
    "analysis_id": "analysis-id",
    "executive_summary": "Summary text"
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

#### Update Report
- **PUT** `/reports/{id}`
- **Headers:** Requires Bearer token (Manager role)
- **Body:**
  ```json
  {
    "executive_summary": "Updated summary"
  }
  ```
- **Response:** `{ success: true, data: { updated report } }`

#### Finalize Report
- **POST** `/reports/{id}/finalize`
- **Headers:** Requires Bearer token (Manager role)
- **Response:** `{ success: true, data: { report with status: "FINALIZED" } }`

## Test Credentials

```
Owner:     owner@example.com / password123ABC!
Manager:   manager@example.com / password123ABC!
Employee1: employee1@example.com / password123ABC!
Employee2: employee2@example.com / password123ABC!
```

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
