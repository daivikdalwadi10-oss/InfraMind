# Copilot / AI Agent Instructions for InfraMind

Purpose: short, actionable guidance so AI coding agents can be productive immediately in this repo.

---

## Big picture (2-3 lines)
- InfraMind is a Next.js (App Router) TypeScript frontend backed by a PHP 8.2+ REST API with SQLite database. UI is Tailwind + shadcn; business logic lives in Next.js Server Actions and PHP backend.
- AI is provided via Genkit (wrapper in `src/ai/genkit.ts`) calling Google Gemini (`gemini-2.5-flash`). All AI calls must be server-side and produce strictly structured JSON.

---

## Key files & where to make changes 🔧
- `src/lib/types.ts` — canonical data models (UserProfile, Task, Analysis, Report). Keep types in sync with PHP backend models.
- `backend/src/Models/` — PHP model classes that map to database tables.
- `backend/database/migrations/001_initial_schema.sql` — SQLite database schema definition.
- `src/lib/auth.ts` — helper asserts (`assertHasRole`, `assertAtLeastRole`) used by Server Actions to re-validate permissions.
- `backend/src/Controllers/AuthController.php` — authentication logic (signup, login, token refresh).
- `backend/public/index.php` — central place for route registration and middleware.
- `src/app/actions.ts` — Next.js Server Actions (calls PHP backend via `callPhpApi`).
- `src/lib/api.ts` — PHP API client wrapper.
- `src/ai/genkit.ts` — minimal Genkit wrapper; use this for all AI interactions. It returns `{ success, data?, error? }`.
- `src/ai/flows/*.ts` — example AI flows: `suggestHypotheses.ts` (returns JSON array of hypotheses) and `draftExecutiveSummary.ts` (returns JSON with `summary`, `highlights`, `recommendedAction`). Follow their structured-output patterns.

---

## Project conventions & important rules (READ CAREFULLY) ⚠️
- No AI calls from the frontend. All Genkit/Gemini usage must be via server-side flows (`src/ai/*`) called by Server Actions.
- All state transitions must occur in Server Actions and must update `statusHistory` (for audits). Server Actions call PHP backend which updates the database.
- PHP backend enforces role-based access control. Always ensure Server Actions validate permissions using `assertHasRole` / `assertAtLeastRole` before calling backend.
- AI must return structured JSON. Flows explicitly parse JSON and throw on parse errors — follow the same pattern (see `suggestHypotheses.ts`).
- Readiness score rules: analyses must be >= 75 to allow submission; enforce this in the submit Server Action.
- **Owners may read `reports` only when `status == 'FINALIZED'`.** Backend enforces this access control.
- Owners are read-only for raw analyses — backend ensures owner access never exposes raw analysis data.

---

## AI flow & prompt guidance (be precise) 🤖
- Use `callGenkit({ model: 'gemini-2.5-flash', prompt, maxTokens })` and handle `{ success, data, error }`.
- Enforce JSON-only responses in prompts (e.g., "Only return valid JSON"), then validate by JSON.parse and shape-check before returning to Server Actions.
- Avoid speculative language in prompts; require professional, factual tone. Example in repo: `draftExecutiveSummary.ts` prompt.
- Validate AI output shapes strictly: e.g., `suggestHypotheses` expects [{text:string, confidence:number, evidence:string[]}].

---

## Dev & run notes (commands & env) ⚡
- Backend: Copy `backend/.env.example` to `backend/.env` and configure database path
- Frontend: Copy `.env.local.example` to `.env.local` and set:
  - `NEXT_PUBLIC_API_URL=http://localhost:8000` (PHP backend URL)
  - `GENKIT_API_KEY` (required for AI flows)
- Common commands:
  - Frontend: `npm install` and `npm run dev`
  - Backend: `cd backend && php -S localhost:8000 -t public router.php`
  - Database: `cd backend && php bin/migrate.php && php bin/seed.php`
  - `npm run dev` (Next dev server)
  - `npm run test:unit` (unit tests)
  - `npm run test:integration` (integration tests against emulator)
  - `npm run coverage` (run coverage for all tests)
- ESLint is strict in CI (`--max-warnings=0`). Add/adjust lint rules in `.eslintrc.json`. Prefer `--fix` locally and fix errors before sending PRs.
- `@typescript-eslint/no-explicit-any` is set to **error**. Replace `any` with precise types (e.g., `unknown` or `Record<string, unknown>`) and prefer typed mocks in tests.
- Admin SDK will throw during server-side operations if `FIREBASE_ADMIN_CREDENTIALS` is misconfigured — check server logs.

---

## When adding features or fixing bugs (checklist) ✅
- Update `src/lib/types.ts` for any model changes and keep in sync with `backend/src/Models/`.
- Update PHP backend models, controllers, and migrations as needed.
- Add/modify Server Actions in `src/app/actions.ts` and re-validate permissions with `src/lib/auth.ts`.
- Ensure Server Actions call PHP backend via `callPhpApi` from `src/lib/api.ts`.
- If adding new AI flows, put them under `src/ai/flows/`, call `callGenkit` from `src/ai/genkit.ts`, enforce JSON-only responses, and add parsing + validation tests.
- Add/adjust `statusHistory` entries for any lifecycle state changes in the database.
- Ensure Owners cannot see raw analyses (enforce in backend controllers).

---

## Server Action templates & tests (examples) 🔧
- Pattern: Server Actions must re-validate permissions with `assertHasRole` / `assertAtLeastRole`, then call PHP backend APIs via `callPhpApi`.

- Example (submit analysis):
```ts
export async function submitAnalysis(analysisId: string) {
  const session = await requireSessionUser();
  await assertHasRole(session.uid, 'EMPLOYEE');

  const response = await callPhpApi('POST', `/analyses/${analysisId}/submit`);
  if (!response.success) throw new Error(response.error || 'Failed to submit analysis');

  return response.data;
}
```

- Example (manager review):
```ts
export async function managerReviewAnalysis(analysisId: string, action: 'APPROVE' | 'REJECT') {
  const session = await requireSessionUser();
  await assertHasRole(session.uid, 'MANAGER');

  const response = await callPhpApi('POST', `/analyses/${analysisId}/review`, { action });
  if (!response.success) throw new Error(response.error || 'Failed to review analysis');

  return response.data;
}
```

- Tests: repo uses Vitest. Add tests for AI flows (preferred) that mock `callGenkit` and assert JSON parsing + normalization.
  - Run: `npm run test`
  - Example: `src/ai/flows/__tests__/suggestHypotheses.test.ts` mocks `callGenkit` to return `{ success: true, data: { text: '[{"text":"x","confidence":80,"evidence":["a"]}]' } }` and asserts normalized result.

---

## CI, Coverage & Notifications 🔔
- Unit tests run on PRs; integration tests run on `main` and nightly. Coverage artifacts are uploaded in CI and [optionally] submitted to Codecov when `CODECOV_TOKEN` is set.
- On CI failures, a GitHub issue is automatically created; if `SLACK_WEBHOOK` secret is set, the workflow will also post a short message to the configured Slack channel.

---

## Failure modes & debugging tips 🐞
- Genkit failures: `src/ai/genkit.ts` returns `success: false` with `error`. Surface meaningful errors to logs and do not commit partial AI outputs to DB.
- Parsing errors: AI sometimes returns non-JSON; flows currently wrap parse in try/catch and throw `Failed to parse Genkit output...` — add richer logging when needed.
- Auth/role issues: verify custom claims or `users` collection data (used by `hasRole()` in rules) and `adminFirestore` contents.
.
- Parsing errors: AI sometimes returns non-JSON; flows currently wrap parse in try/catch and throw `Failed to parse Genkit output...` — add richer logging when needed.
- Auth/role issues: verify session cookies and backend user data in SQLite database.
- API errors: Check PHP backend logs in `backend/logs/` directory.
- Database issues: Verify SQLite database exists at `backend/database.sqlite`
If anything here is unclear or you want additional examples (e.g., full Server Action templates for submit/review/report flows), say which area you want expanded and I will update this document.