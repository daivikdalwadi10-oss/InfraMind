# Changelog

## 2026-02-01 — Security fixes (PR #1)

- **Security**: Remediated vulnerabilities reported by `npm audit` and tightened dev tooling.
  - Upgraded `lint-staged` to the latest release to resolve `micromatch` issues.
  - Pinned `firebase-admin` to `12.7.0` to pick up patched dependencies.
  - Added an `overrides` entry to enforce `fast-xml-parser@5.3.4` (transitive fix via `@google-cloud/storage`).
  - Added a robust fetch fallback in `src/ai/genkit.ts` (prefer global `fetch`, else `node-fetch`).
- Validation: Unit tests pass, TypeScript checks pass, and `npm audit` reports no vulnerabilities.

Merge: PR #1 (squash-merged commit `d3cd9b6`) — see https://github.com/daivikdalwadi10-oss/InfraMind/pull/1

> Note: Temporary PATs used during automation were cleared from the environment. Revoke any tokens you created in GitHub for extra safety.
