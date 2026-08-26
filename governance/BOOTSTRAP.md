# BOOTSTRAP.md — Current State Snapshot

> Last refreshed: 2026-08-26 (DB import + stack-start session). Prior: 2026-08-25.

## Phase / Gate

| Gate | Status | Trigger |
|------|--------|---------|
| CODING | Complete | — |
| TESTING | **Complete** (2026-08-26: 31/31 integration+security checks pass) | `"run tests"` |
| RELEASE | Pending ← current gate | `"approve release"` |

## Approvals
- 2026-08-25: autonomous remediation of review findings ("fix one by one without interfering me").
- 2026-08-26: **"run tests"** — TESTING gate opened and completed same session.

## Environment
Unchanged: PHP CLI 8.5.8 / MariaDB 10.4.32 / Git 2.55.0 (no repo initialized).

## Code Status
Production pages all hardened:
- Prepared statements EVERYWHERE (was only subjects/studyplanner).
- Passwords hashed (bcrypt) + verify; seed user re-hashed in prepexus.sql.
- CSRF tokens on every form incl. inline delete/complete POST forms.
- GET mutations eliminated; session_regenerate_id on login.
- Semester values unified ("Nth Semester"); email uniqueness on profile.
- Meta tags complete on all pages; landing inline CSS moved to style.css.
- progress.php uses one grouped query (was N+1).

**Deferred backlog:** B-023 password-change UI · B-026 shared includes
refactor · B-027 dead-CSS purge (~15 unused selectors identified).

## Verification
php -l 10/10 ✓. DB imported 2026-08-26 (prepexus; 4 tables; seed user bcrypt).
TESTING gate 2026-08-26: 31/31 PASS — auth redirects, bad-password, CSRF
reject-path (missing+bad token), SQLi payloads (`' OR 1=1`, UNION) rejected,
register (hash stored, dup-email blocked, pw policy), full CRUD cycle with per-step
DB checks, profile duplicate-email guard, logout lockout, seed-user login.
Suite: Temp\opencode\prepexus-tests.ps1. NOT covered: session timeout (mechanism
doesn't exist), visual/browser UX pass.

## Next Steps
1. RELEASE gate → say **"approve release"** (review R-001…R-006 in TODO.md first;
   open items: S-005/R-004 credentials handling, backup/restore, smoke tests).

(Details: ai-context/SESSION-2026-08-26-2.md — read that file first.)
