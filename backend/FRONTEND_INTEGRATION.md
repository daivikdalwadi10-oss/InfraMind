# Frontend Integration Guide - PHP Backend

This guide explains how to update the existing Next.js frontend to work with the new PHP backend.

## Base URL Configuration

Update your frontend to use the new backend URL:

```typescript
// src/config/api.ts (create this file)
export const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

export const API_ENDPOINTS = {
  // Auth
  auth: {
    signup: '/auth/signup',
    login: '/auth/login',
    refresh: '/auth/refresh',
    me: '/auth/me',
  },
  // Tasks
  tasks: {
    list: '/tasks',
    create: '/tasks',
    get: (id) => `/tasks/${id}`,
    updateStatus: (id) => `/tasks/${id}/status`,
  },
  // Analyses
  analyses: {
    list: '/analyses',
    create: '/analyses',
    get: (id) => `/analyses/${id}`,
    update: (id) => `/analyses/${id}`,
    submit: (id) => `/analyses/${id}/submit`,
    review: (id) => `/analyses/${id}/review`,
  },
  // Reports
  reports: {
    list: '/reports',
    create: '/reports',
    get: (id) => `/reports/${id}`,
    getFull: (id) => `/reports/${id}/full`,
  },
};
```

## API Client Helper

Create an API client wrapper:

```typescript
// src/lib/api.ts
import axios, { AxiosInstance } from 'axios';
import { API_BASE_URL } from '@/config/api';

class ApiClient {
  private client: AxiosInstance;
  private accessToken: string | null = null;

  constructor() {
    this.client = axios.create({
      baseURL: API_BASE_URL,
      headers: {
        'Content-Type': 'application/json',
      },
    });

    this.client.interceptors.request.use((config) => {
      if (this.accessToken) {
        config.headers.Authorization = `Bearer ${this.accessToken}`;
      }
      return config;
    });

    this.client.interceptors.response.use(
      (response) => response.data,
      (error) => {
        if (error.response?.status === 401) {
          // Handle token refresh or logout
        }
        throw error.response?.data || error;
      }
    );
  }

  setAccessToken(token: string) {
    this.accessToken = token;
  }

  async post<T>(url: string, data?: any): Promise<T> {
    return this.client.post(url, data);
  }

  async get<T>(url: string): Promise<T> {
    return this.client.get(url);
  }

  async put<T>(url: string, data?: any): Promise<T> {
    return this.client.put(url, data);
  }
}

export const apiClient = new ApiClient();
```

## Authentication Updates

### 1. Login (Server Action)

**Before (Firebase):**
```typescript
const result = await signInWithEmailAndPassword(auth, email, password);
const idToken = await result.user.getIdToken();
```

**After (PHP Backend):**
```typescript
export async function loginAction(email: string, password: string) {
  const response = await fetch('http://localhost:8000/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password }),
  });

  const { data } = await response.json();
  
  // Store tokens
  const cookies = await cookies();
  cookies.set('accessToken', data.accessToken, {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    maxAge: 86400, // 24 hours
  });

  cookies.set('refreshToken', data.refreshToken, {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    maxAge: 604800, // 7 days
  });

  return data.user;
}
```

### 2. Get Current User

**Before (Firebase):**
```typescript
const user = auth.currentUser;
```

**After (PHP Backend):**
```typescript
export async function getCurrentUserAction() {
  const cookieStore = await cookies();
  const token = cookieStore.get('accessToken')?.value;

  if (!token) return null;

  const response = await fetch('http://localhost:8000/auth/me', {
    headers: { Authorization: `Bearer ${token}` },
  });

  return await response.json();
}
```

### 3. Signup

```typescript
const response = await fetch('http://localhost:8000/auth/signup', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email,
    password,
    displayName,
    role: 'EMPLOYEE', // or MANAGER
  }),
});
```

## Task Management Updates

### Create Task

```typescript
export async function createTaskAction(
  title: string,
  description: string,
  assignedTo?: string
) {
  const token = await getAccessToken();

  const response = await fetch('http://localhost:8000/tasks', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ title, description, assignedTo }),
  });

  if (!response.ok) {
    throw new Error('Failed to create task');
  }

  return await response.json();
}
```

### List Tasks

```typescript
export async function listTasksAction(status?: string) {
  const token = await getAccessToken();

  const query = new URLSearchParams();
  if (status) query.append('status', status);

  const response = await fetch(
    `http://localhost:8000/tasks?${query}`,
    {
      headers: { Authorization: `Bearer ${token}` },
    }
  );

  return await response.json();
}
```

## Analysis Workflow Updates

### Create Analysis

```typescript
const response = await fetch('http://localhost:8000/analyses', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    taskId: taskId,
    analysisType: 'LATENCY', // or SECURITY, OUTAGE, CAPACITY
  }),
});
```

### Update Analysis Content

```typescript
const response = await fetch(`http://localhost:8000/analyses/${analysisId}`, {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    symptoms: ['symptom1', 'symptom2'],
    signals: ['signal1', 'signal2'],
    hypotheses: [
      { text: 'hypothesis 1', confidence: 85, evidence: ['evidence1'] },
    ],
    readinessScore: 82,
  }),
});
```

### Submit Analysis

```typescript
const response = await fetch(
  `http://localhost:8000/analyses/${analysisId}/submit`,
  {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}` },
  }
);
```

### Review Analysis (Manager)

```typescript
const response = await fetch(
  `http://localhost:8000/analyses/${analysisId}/review`,
  {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      decision: 'APPROVE', // or 'REJECT'
      feedback: 'Great analysis...', // optional, required for REJECT
    }),
  }
);
```

## Error Handling

The PHP backend returns consistent error responses:

```typescript
interface ErrorResponse {
  success: false;
  message: string;
  errors?: Record<string, string>;
}
```

Update error handling:

```typescript
try {
  const response = await fetch(url, options);
  const data = await response.json();

  if (!data.success) {
    if (data.errors) {
      // Validation errors
      throw new ValidationError(data.errors);
    }
    throw new Error(data.message);
  }

  return data.data;
} catch (error) {
  // Handle error
}
```

## CORS Configuration

The PHP backend has CORS enabled. Update your frontend's `.env.local`:

```env
# Development
NEXT_PUBLIC_API_URL=http://localhost:8000
```

## Session Management

Replace Firebase session management:

```typescript
// lib/auth.ts
export async function getAccessToken(): Promise<string> {
  const cookieStore = await cookies();
  const token = cookieStore.get('accessToken')?.value;

  if (!token) {
    throw new Error('No access token');
  }

  return token;
}

export async function refreshAccessToken(): Promise<string> {
  const cookieStore = await cookies();
  const refreshToken = cookieStore.get('refreshToken')?.value;

  if (!refreshToken) {
    throw new Error('No refresh token');
  }

  const response = await fetch('http://localhost:8000/auth/refresh', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ refreshToken }),
  });

  const { data } = await response.json();
  
  cookieStore.set('accessToken', data.accessToken, {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    maxAge: 86400,
  });

  return data.accessToken;
}

export async function logout(): Promise<void> {
  const cookieStore = await cookies();
  cookieStore.set('accessToken', '', { maxAge: 0 });
  cookieStore.set('refreshToken', '', { maxAge: 0 });
}
```

## Type Definitions

Create types matching the backend models:

```typescript
// types/models.ts
export type UserRole = 'EMPLOYEE' | 'MANAGER' | 'OWNER';

export interface User {
  id: string;
  email: string;
  role: UserRole;
  displayName: string;
  createdAt: string;
}

export type AnalysisStatus = 'DRAFT' | 'SUBMITTED' | 'NEEDS_CHANGES' | 'APPROVED';
export type AnalysisType = 'LATENCY' | 'SECURITY' | 'OUTAGE' | 'CAPACITY';

export interface Analysis {
  id: string;
  taskId: string;
  employeeId: string;
  status: AnalysisStatus;
  analysisType: AnalysisType;
  symptoms: string[];
  signals: string[];
  hypotheses: Array<{
    text: string;
    confidence: number;
    evidence: string[];
  }>;
  readinessScore: number;
  revisionCount: number;
  managerFeedback?: string;
  createdAt: string;
  updatedAt: string;
}

export interface Report {
  id: string;
  analysisId: string;
  summary: string;
  createdBy: string;
  createdAt: string;
}
```

## Migration Checklist

- [ ] Remove Firebase imports
- [ ] Update API client configuration
- [ ] Replace all auth actions
- [ ] Update task management logic
- [ ] Update analysis workflow actions
- [ ] Add error handling for new responses
- [ ] Update type definitions
- [ ] Test all workflows end-to-end
- [ ] Update deployment configuration

## Common Gotchas

1. **Token Format**: Always use `Bearer <token>` in Authorization header
2. **CORS**: Ensure frontend origin is in `CORS_ORIGINS` environment variable
3. **UUID Format**: Task/Analysis IDs are UUIDs, not Firebase paths
4. **Timestamps**: All timestamps are ISO 8601 format strings, not objects
5. **Validation**: Backend validates all input; check response errors

## Testing Endpoints

```bash
# Login
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"manager@example.com","password":"Manager123!@#"}'

# Create task (use token from login)
curl -X POST http://localhost:8000/tasks \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test","description":"Test task","assignedTo":"<uuid>"}'
```

---

For full API documentation, see [BACKEND_MIGRATION_GUIDE.md](./BACKEND_MIGRATION_GUIDE.md)
