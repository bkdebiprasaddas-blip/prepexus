# BOOTSTRAP.md — Current State Snapshot

> Last refreshed: 2026-09-08 (presentation deliverable). Prior: CODING/TESTING/RELEASE status (2026-08-29). Real envs: PHP 8.5.8 cli / MariaDB 10.4.32 / Git 2.55.0.

## Phase / Gate

| Gate | Status | Trigger |
|------|--------|---------|
| CODING | Complete | — |
| TESTING | **Complete** (2026-08-26: 31/31 integration+security checks pass) | `"run tests"` |
| RELEASE | Pending ← current gate | `"approve release"` |

## Deliverable (non-code, viva)
- **PRESENTATION created:** `presentation/PREPEXUS-Presentation.pptx` (14 slides, colourful, academic-viva tone). Built with **python-pptx 1.0.2** (dev tool, installed to user site; nothing added to the project). Generator script and verifier live in git-ignored `Scratch/prepexus/presentation/`. No production PHP was modified. Slides cover: problem, solution, features, tech stack, architecture & roles, data model, key screens, security, admin panel, testing (31/31), challenges, future scope, thank-you.
- Verified loadable by both `python-pptx` (14 slides) and PowerPoint COM automation (14 slides, no error).

## Approvals
- 2026-08-25: autonomous remediation of review findings ("fix one by one without interfering me").
- 2026-08-26: **"run tests"** — TESTING gate opened and completed same session.
- 2026-08-26: owner-directed git remote reset — "delete old remote git… push new to
  remote". Fresh history `dce738a` force-pushed to origin/main (old history removed).
- 2026-08-29: **"code it"** — Admin role and Admin Panel implementation authorized & completed.
- 2026-09-08: householder consent inferred from "plan and make a powerpoint ppt" — build method (python-pptx) and output location (root `presentation/`) chosen per clarified defaults.

## Environment
Unchanged: PHP CLI 8.5.8 / MariaDB 10.4.32 / Git 2.55.0.
Git (2026-08-26): local repo initialized; origin = github.com/bkdebiprasaddas-blip/prepexus.git;
fresh single-commit history `dce738a` force-pushed; main tracks origin/main, in sync.

## Code Status
Production pages hardened & updated:
- **Admin Role & Panel Added:**
  - `role` ENUM column (`student`, `admin`) added to `users` table in database and `prepexus.sql`.
  - Default admin seeded: `admin@prepexus.com` / `admin123`.
  - Shared `login.php` updated to inspect `role` and route admin users to `admin/dashboard.php`.
  - Created `admin/auth_guard.php` enforcing admin session authorization.
  - Created Admin Panel sub-system:
    - `admin/dashboard.php`: Platform metrics (Total Students, Subjects, Tasks, Materials) & quick links.
    - `admin/users.php`: User management (View, Edit Role, Delete User with cascade data cleanup).
    - `admin/subjects.php`: Platform-wide subject overview & deletion.
    - `admin/materials.php`: Platform-wide materials overview & deletion.
- Prepared statements EVERYWHERE (was only subjects/studyplanner).
- Passwords hashed (bcrypt) + verify; seed user re-hashed in prepexus.sql.
- CSRF tokens on every form incl. inline delete/complete POST forms and admin forms.
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
