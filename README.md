# InfraMind

InfraMind is a production-grade internal tool built with Next.js (App Router), TypeScript, TailwindCSS, Firebase Auth & Firestore, and Google Gemini via Genkit for AI-assisted decision support.

Features:
- Role-based dashboards (Employee, Manager, Owner)
- Server Actions for all business logic
- Firestore security rules and strict role enforcement
- AI flows using Genkit + Gemini (server-side only)

Setup:
1. Copy `.env.example` to `.env.local` and fill in env vars.
2. Install dependencies: `npm install`
3. Run dev server: `npm run dev`

Note: This repository includes scaffolding only. Fill in Firebase credentials and Genkit API keys before running.

Integration tests
- Integration tests exercise `firestore.rules` and require the Firestore emulator to be running or the environment variables `FIRESTORE_EMULATOR_HOST` / `FIREBASE_EMULATOR_HOST` to be set. Run via `firebase emulators:exec "npm run test:integration"` or set the env vars before running locally.

CI & Notifications
- Set `CODECOV_TOKEN` (Repo secret) to upload coverage reports to Codecov. If unset, coverage artifact is still uploaded to the workflow.
- Set `SLACK_WEBHOOK` (Repo secret) to post CI failure messages to Slack; otherwise the workflow will create a GitHub issue on CI failures.

Codecov badge (add after you register repo on Codecov):

[![Codecov](https://codecov.io/gh/<owner>/<repo>/branch/main/graph/badge.svg)](https://codecov.io/gh/<owner>/<repo>)

Linting
- ESLint is strict and runs with `--max-warnings=0` in CI; fix lint warnings locally with `npm run lint` and address issues before opening PRs.
