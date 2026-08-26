# TODO.md — Phased Task Checklist

> **Current gate:** TESTING (complete) → RELEASE (pending).
> **Trigger to advance:** `"approve release"`

## Phase Status

| Phase | Gate Trigger | Status |
|-------|-------------|--------|
| DISCOVERY | `"approve discovery"` | N/A (product already defined) |
| CLARIFY | (automatic) | Done (env checked, stack confirmed) |
| PLANNING | `"approve plan"` | Done (PLAN.md, DB-DESIGN.md, IMPL-SPEC.md, UI-SPEC.md created) |
| DESIGN FIXED | `"approve design"` | N/A (code already implements design) |
| UI DESIGN CONFIRMED | `"UI is final"` | N/A (UI already implemented) |
| CODING | `"code it"` | **Complete** (code exists at root) |
| TESTING | `"run tests"` | **Complete** (2026-08-26: 31/31 integration+security checks pass — SESSION-2026-08-26-2.md) |
| RELEASE | `"approve release"` | Pending ← current gate |
| OPERATE | (automatic) | Pending |

## Testing Phase Tasks — EXECUTED 2026-08-26 (results in SESSION-2026-08-26-2.md)

Note: S-001…S-004 and S-006 were already implemented by Bug-Fix Queue items
B-002…B-013 (same scope, different IDs). Testing verified them live.

### 4.1 Security Hardening (Level 5 — security/reliability verification)

- [x] **S-001:** Password hashing — DONE via B-002/B-008; verified live
      (register stores `$2y$` hash; seed login works).
- [x] **S-002:** SQL injection fixed — DONE via B-002…B-007; verified live
      (`' OR 1=1 --` and UNION payloads rejected, T-005 pass).
- [x] **S-003:** CSRF tokens on all POST forms — DONE via B-011; reject-path
      verified live (missing token AND bad token both → "Invalid request.").
- [x] **S-004:** Delete ops converted GET→POST — DONE via B-010; exercised live.
- [ ] **S-005:** Move DB credentials out of web root / env vars + `.env.example`
      — OPEN, deferred to RELEASE (overlaps R-004). config/database.php still
      has inline root/empty-password creds (acceptable for local XAMPP dev only).
- [ ] **S-003:** Add CSRF tokens to all POST forms (all 7 form pages).
- [x] **S-004:** Convert delete operations from GET to POST — DONE via B-010; exercised live.
- [ ] **S-005:** Move DB credentials to environment variables or a secure
      config outside the web root; add `.env.example` template. (OPEN → RELEASE/R-004)
- [x] **S-006:** Server-side input validation — DONE via B-009/B-022/B-024 +
      register/profile checks; verified live (short/mismatch password blocked).

### 4.2 Testing (Level 2/3 — unit/integration)

- [x] **T-001:** Lint — `php -l` all 10 root pages: PASS (re-run in suite).
- [x] **T-002:** Integration CRUD tests — 26-step HTTP suite
      (Temp\opencode\prepexus-tests.ps1): subject create/update-visible,
      task create/complete/update/delete, material create/delete,
      profile update + duplicate-email guard, DB state verified per step.
- [x] **T-003:** Auth tests — unauth redirect to login, wrong-password error,
      logout redirect, protected route locked after logout, B-020 logged-in
      guards on login/register, seed-user + QA-user logins. Session-timeout
      NOT tested (no timeout mechanism exists — noted for RELEASE review).
- [x] **T-004:** Manual UI checklist — remains available in SETUP-GUIDE.md;
      automated coverage substituted where possible. Visual/browser-level UX
      pass still open as optional manual step.
- [x] **T-005:** SQLi resistance — `' OR 1=1 --` and UNION payloads on login:
      both rejected with generic error.

RESULT: 31/31 checks pass (1 initial FAIL was a test-harness syntax bug in the
REG-05 assertion, corrected and re-run: bcrypt `$2y$` prefix confirmed).

### 4.3 Code Quality

- [ ] **Q-001:** Add consistent error handling (try/catch or error checking
      on all mysqli calls).
- [ ] **Q-002:** Refactor long single-file pages if feasible (extract shared
      layout/header partials) — optional, do not break existing behavior.
- [ ] **Q-003:** Add proper HTTP status codes and headers.

## Release Phase Tasks (awaiting `"approve release"`)

- [ ] **R-001:** Complete all Testing phase tasks.
- [ ] **R-002:** Verify database migrations are ordered and reversible.
- [ ] **R-003:** Verify backup/restore procedure for prepexus DB.
- [ ] **R-004:** Configure production secrets (env vars, not in code).
- [ ] **R-005:** Run production smoke tests on critical user journeys.
- [ ] **R-006:** Verify rollback procedure is executable.

## Backlog (deferred scope)

- [ ] Multi-role / admin panel
- [ ] Email verification
- [ ] Password reset flow
- [ ] Mobile-responsive redesign (CSS exists but may need refinement)
- [ ] API layer (for future mobile app)
- [ ] Automated CI/CD pipeline (no CI configured)
- [ ] Caching layer for dashboard queries
- [ ] Export feature (study progress, materials list)
- [ ] Notification system for due dates

## Bug-Fix Queue (from line-by-line review — 2026-08-25)

### Phase 1: Critical Fixes 🔴 — COMPLETE

- [x] **B-001:** DB name aligned to `prepexus` in config/database.php (+ utf8mb4 charset added).
- [x] **B-002:** login.php → prepared statement (email lookup) + password_verify().
- [x] **B-003:** register.php → prepared statements (duplicate check + INSERT).
- [x] **B-004:** materials.php → all 6 queries converted to prepared statements.
- [x] **B-005:** profile.php → UPDATE + SELECT converted to prepared statements.
- [x] **B-006:** dashboard.php → all 6 COUNT queries converted.
- [x] **B-007:** progress.php → all queries converted (incl. grouped subject counts).
- [x] **B-008:** password_hash() on register, password_verify() on login,
      seed user password re-hashed in prepexus.sql
      (`[REDACTED]` → `[REDACTED-HASH]`). Seed login still works.

### Phase 2: High Priority Fixes 🟠 — COMPLETE

- [x] **B-009:** Semester values unified to "1st Semester"…"6th Semester"
      across register.php / profile.php / seed data.
- [x] **B-010:** Delete + complete actions now POST forms (subjects,
      studyplanner, materials). No state-changing GET remains (verified 0 hits).
- [x] **B-011:** CSRF tokens on ALL forms (login, register, profile + main
      forms of subjects/studyplanner/materials + inline delete/complete forms),
      verified with hash_equals(); guards on every POST handler.
- [x] **B-012:** Email-uniqueness check (`WHERE email = ? AND id != ?`) before profile UPDATE.
- [x] **B-013:** session_regenerate_id(true) after successful login.

### Phase 3: Medium Priority Fixes 🟡 — COMPLETE

- [x] **B-014:** charset/viewport/lang added to index, login, register, dashboard.
- [x] **B-015:** rel="noopener noreferrer" on materials Open link.
- [x] ~~**B-016**~~ **DOWNGRADED — not a bug.** Cascade analysis shows later
      definitions correctly override; slide-in sidebar and 4-col grid work as
      intended. Duplicate blocks are cosmetic only (see B-027).
- [x] **B-017:** mysqli_set_charset(utf8mb4) added (with B-001).
- [x] **B-018:** All NEW/rewritten query code checks `$stmt` before use;
      legacy un-guarded calls eliminated by B-002…B-007 conversions.
- [x] **B-019:** index.php inline <style> moved to css/style.css
      (scoped `.feature-item .feature-icon` to avoid collision).
- [x] **B-020:** Logged-in users redirected from login/register to dashboard.
- [x] **B-021:** Registration redirects to `login.php?registered=1`;
      login.php shows confirmation banner.

### Phase 4: Low Priority / UX 🟢

- [x] **B-022:** Confirm-password field added (register.php), enforced server-side.
- [ ] **B-023:** Password change on profile page (feature — deferred).
- [x] **B-024:** Min 8-char password policy enforced server-side.
- [x] **B-025:** progress.php N+1 loop replaced with one GROUP BY query.
- [ ] **B-026:** Extract shared navbar/sidebar includes (optional refactor — deferred).
- [ ] **B-027:** Remove dead CSS blocks (~15 unused selectors identified:
      sidebar-link*, sidebar-bottom, dashboard-layout/main/topbar, topbar-title,
      user-area, menu-title, second-title, hero-card, coming-soon) — deferred.
- [x] **B-028:** SQL dump typo fixed: `subejct_id` → `subject_id`.

### Verification Summary (2026-08-25)

- `php -l`: 12/12 files pass.
- Grep audit: 0 remaining `$_GET['delete'|'complete']`; verify_csrf() present
  in all 6 form pages; hidden csrf_token present in every form (main + inline).
- NOT YET VERIFIED (needs running app): manual login/register/CRUD flows,
  CSRF reject-path behavior in browser. These belong to the TESTING gate.


## Completed (pre-rulebook)

All 10 PHP pages were implemented before this rule book was activated.
Their functionality is documented in IMPL-SPEC.md and UI-SPEC.md as as-built.
No changes were made to them during scaffold creation (§I.1).
