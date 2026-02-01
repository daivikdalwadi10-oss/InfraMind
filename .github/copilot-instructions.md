# Copilot / AI Agent Instructions for InfraMind

Purpose: short, actionable guidance so AI coding agents can be productive immediately in this repo.

---

## Big picture (2-3 lines)
- InfraMind is a Next.js (App Router) TypeScript app backed by Firebase Auth + Firestore. UI is Tailwind + shadcn; business logic lives in Next.js Server Actions and runs server-side only.
- AI is provided via Genkit (wrapper in `src/ai/genkit.ts`) calling Google Gemini (`gemini-2.5-flash`). All AI calls must be server-side and produce strictly structured JSON.

---

## Key files & where to make changes 🔧
- `src/lib/types.ts` — canonical data models (UserProfile, Task, Analysis, Report). Keep types and Firestore schema in sync.
- `firestore.rules` — the final authority for role-based access. Any server-side changes must be reconciled with rules.
- `src/lib/auth.ts` — helper asserts (`assertHasRole`, `assertAtLeastRole`) used by Server Actions to re-validate permissions.
- `src/firebase/admin.ts` & `src/firebase/client.ts` — admin vs client SDK initialization; check env vars `FIREBASE_ADMIN_CREDENTIALS` and `NEXT_PUBLIC_FIREBASE_*`.
- `src/app/actions.ts` — central place for Server Actions (business logic must live here; NO client-side business logic).
- `src/ai/genkit.ts` — minimal Genkit wrapper; use this for all AI interactions. It returns `{ success, data?, error? }`.
- `src/ai/flows/*.ts` — example AI flows: `suggestHypotheses.ts` (returns JSON array of hypotheses) and `draftExecutiveSummary.ts` (returns JSON with `summary`, `highlights`, `recommendedAction`). Follow their structured-output patterns.

---

## Project conventions & important rules (READ CAREFULLY) ⚠️
- No AI calls from the frontend. All Genkit/Gemini usage must be via server-side flows (`src/ai/*`) called by Server Actions.
- All state transitions must occur in Server Actions and must update `statusHistory` (for audits). Update both the Firestore document and the history array atomically.
- Firestore security rules are the final authority; always ensure server-side validations mirror rule constraints. If you add a new status or field, update both `src/lib/types.ts` and `firestore.rules`.
- AI must return structured JSON. Flows explicitly parse JSON and throw on parse errors — follow the same pattern (see `suggestHypotheses.ts`).
- Use `assertHasRole` / `assertAtLeastRole` from `src/lib/auth.ts` in Server Actions to validate permissions, even if rules already enforce it.
- Readiness score rules: analyses must be >= 75 to allow submission; enforce this in the submit Server Action.
- **Owners may read `reports` only when `status == 'FINALIZED'`.** If you change report lifecycle states, update `firestore.rules` and integration tests accordingly.
- Owners are read-only for raw analyses — ensure owner access never exposes raw analysis docs directly.

---

## AI flow & prompt guidance (be precise) 🤖
- Use `callGenkit({ model: 'gemini-2.5-flash', prompt, maxTokens })` and handle `{ success, data, error }`.
- Enforce JSON-only responses in prompts (e.g., "Only return valid JSON"), then validate by JSON.parse and shape-check before writing results to Firestore.
- Avoid speculative language in prompts; require professional, factual tone. Example in repo: `draftExecutiveSummary.ts` prompt.
- Validate AI output shapes strictly: e.g., `suggestHypotheses` expects [{text:string, confidence:number, evidence:string[]}].

---

## Dev & run notes (commands & env) ⚡
- Copy `.env.example` to `.env.local` and set:
  - `GENKIT_API_KEY` (required for AI flows)
  - `FIREBASE_ADMIN_CREDENTIALS` (JSON string for admin SDK or rely on ADC during dev)
  - `NEXT_PUBLIC_FIREBASE_*` (client config)
- Common commands:
  - `npm install`
  - `npm run dev` (Next dev server)
  - `npm run test:unit` (unit tests)
  - `npm run test:integration` (integration tests against emulator)
  - `npm run coverage` (run coverage for all tests)
- ESLint is strict in CI (`--max-warnings=0`). Add/adjust lint rules in `.eslintrc.json`. Prefer `--fix` locally and fix errors before sending PRs.
- `@typescript-eslint/no-explicit-any` is set to **error**. Replace `any` with precise types (e.g., `unknown` or `Record<string, unknown>`) and prefer typed mocks in tests.
- Admin SDK will throw during server-side operations if `FIREBASE_ADMIN_CREDENTIALS` is misconfigured — check server logs.

---

## When adding features or fixing bugs (checklist) ✅
- Update `src/lib/types.ts` for any model changes.
- Update `firestore.rules` to reflect access changes.
- Add/modify Server Actions in `src/app/actions.ts` and re-validate permissions with `src/lib/auth.ts`.
- If adding new AI flows, put them under `src/ai/flows/`, call `callGenkit` from `src/ai/genkit.ts`, enforce JSON-only responses, and add parsing + validation tests.
- Add/adjust `statusHistory` entries for any lifecycle state changes.
- Ensure Owners cannot see raw analyses (update rules + server-side checks).

---

## Server Action templates & tests (examples) 🔧
- Pattern: Server Actions must re-validate permissions with `assertHasRole` / `assertAtLeastRole`, perform server-side checks, update Firestore atomically, and append `statusHistory` using `admin.firestore.FieldValue.arrayUnion(...)`.

- Example (submit analysis):
```ts
export async function submitAnalysis(employeeUid: string, analysisId: string) {
  await assertHasRole(employeeUid, 'employee');
  const snap = await adminFirestore.collection('analyses').doc(analysisId).get();
  if (!snap.exists) throw new Error('Analysis not found');
  const analysis = snap.data() as Analysis;
  if (analysis.author !== employeeUid) throw new Error('Not the author');
  if (analysis.status !== 'DRAFT') throw new Error('Only DRAFT can be submitted');

  // compute readiness and require >=75
  const score = computeReadiness(analysis);
  if (score < 75) throw new Error('Readiness score must be ≥ 75 to submit');

  const now = Date.now();
  const statusEntry = { status: 'SUBMITTED', changedAt: now, changedBy: employeeUid };
  await adminFirestore.collection('analyses').doc(analysisId).update({ status: 'SUBMITTED', readinessScore: score, updatedAt: now, statusHistory: admin.firestore.FieldValue.arrayUnion(statusEntry) });
}
```

- Example (manager review → approve & draft report):
```ts
export async function managerReviewAnalysis(managerUid, analysisId, action) {
  await assertHasRole(managerUid, 'manager');
  // verify submitted, then update statusHistory via admin.firestore.FieldValue.arrayUnion
  // If APPROVE: call AI flow `draftExecutiveSummary(...)` from `src/ai/flows` and create a `reports` draft entry with `executiveSummaryDraft` filled.
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

---

If anything here is unclear or you want additional examples (e.g., full Server Action templates for submit/review/report flows), say which area you want expanded and I will update this document.