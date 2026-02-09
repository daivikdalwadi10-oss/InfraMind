# InfraMind Frontend

InfraMind frontend built with Next.js (App Router), TypeScript, Tailwind CSS, and shadcn/ui.

## Overview

The frontend provides role-aware navigation, dashboards, and workflow UIs. All data access is handled through the PHP backend via the API client.

## Tech Stack

- Next.js 16 + React 18
- TypeScript
- Tailwind CSS + shadcn/ui
- Lucide icons

## Local Setup

```powershell
cd frontend
npm install
cp .env.local.example .env.local
npm run dev
```

## Environment Variables (No Secrets)

- `NEXT_PUBLIC_API_URL` -> backend base URL

## Scripts

```powershell
npm run dev       # Start dev server
npm run build     # Production build
npm run start     # Serve production build
npm run lint      # Lint code
npm run typecheck # TypeScript checks
```

## Notes

- All AI and auth logic is server-side. The frontend does not call AI providers directly.
- Role-based access is enforced by the backend; the UI reflects permissions.
