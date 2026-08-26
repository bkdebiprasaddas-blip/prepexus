# SECURITY-THREAT-MODEL.md

> **REQUIRED** — application handles user accounts, passwords, and personal data.
> **Rule refs:** §J0T (security defaults), §J15C (data lifecycle), §J0M (object-level auth), §J12C (verification levels).

## 1. Assets

| Asset | Classification | Description |
|-------|---------------|-------------|
| User passwords | High (sensitive) | Currently stored in **plaintext** — CRITICAL |
| User PII | Medium | name, email, course, semester |
| Study materials | Low-Medium | user-owned notes, links, descriptions |
| Study tasks | Low-Medium | user-owned task data |
| Session tokens | Medium | PHP session IDs |
| DB credentials | Medium | localhost/root/empty password in config/database.php |

## 2. Trust Boundaries

```
[Browser] → (HTTP POST/GET) → [Apache/PHP] → (mysqli) → [MariaDB]
```

- The browser is **untrusted** — all user input must be validated.
- PHP is **trusted** but the code has vulnerabilities.
- The database is **trusted** but the app doesn't properly sanitize all queries.
- `config/database.php` credentials are committed to the codebase (local-dev acceptable, production risk).

## 3. Threats & Abuse Cases

### T-001: SQL Injection (CRITICAL)

**Affected files:** login.php:14, register.php:15/23, materials.php:35/69/96/129,
profile.php:35/64, dashboard.php (count queries), progress.php (count queries)

**Attack vector:** A malicious user submits crafted input in the email, password,
or other form fields. Since queries use string interpolation (e.g.,
`WHERE email='$email'`), an attacker could inject SQL like:
`' OR '1'='1` to bypass authentication, or `'; DROP TABLE users; --` to destroy data.

**Impact:** Authentication bypass, data exfiltration, data destruction.

**Current status:** NOT FIXED. subjects.php and studyplanner.php already use
prepared statements (secure pattern).

**Mitigation required:**
- Convert all `mysqli_query($conn, "...")` calls to `mysqli_prepare` +
  `bind_param` pattern (like subjects.php/studyplanner.php).
- Never interpolate user input into SQL strings.
- Rule refs: §J12C (Level 5 security verification), §J0T (input validation, output encoding).

### T-002: Plaintext Password Storage (CRITICAL)

**Affected files:** register.php:23 (insert), login.php:14 (compare),
prepexus.sql (seed user password stored in plaintext)

**Attack vector:** Database compromise exposes all user passwords directly.

**Impact:** Credential stuffing, account takeover on other services if users
reuse passwords.

**Current status:** NOT FIXED.

**Mitigation required:**
- `register.php`: `$hash = password_hash($password, PASSWORD_DEFAULT);`
- `login.php`: fetch password hash, use `password_verify($password, $stored_hash)`.
- Migrate existing user passwords (force password reset or rehash on next login).
- Rule refs: §J0T (secure defaults), §J15C (data lifecycle), §J15F (if AI used for auth logic).

### T-003: Cross-Site Request Forgery (CSRF) (HIGH)

**Affected files:** All POST forms (login, register, subjects, studyplanner,
materials, profile) and GET-based mutations (delete, complete).

**Attack vector:** An attacker crafts a malicious page that submits a form to
the application while the user is logged in. Since there's no CSRF token, the
request succeeds.

**Impact:** Unauthorized data modification or deletion.

**Current status:** NOT FIXED.

**Mitigation required:**
- Add per-session CSRF tokens to all forms.
- Convert GET-based mutations (delete, complete) to POST with tokens.
- Rule refs: §J0T (secure defaults), §J0L (API compatibility).

### T-004: Delete via GET Parameter (MEDIUM)

**Affected files:** subjects.php:137, studyplanner.php:135, materials.php:64

**Attack vector:** A malicious link or image tag (`<img src="subjects.php?delete=1">`)
triggers deletion when visited by a logged-in user.

**Impact:** Unauthorized data deletion.

**Mitigation required:**
- Convert all delete operations to POST forms with CSRF tokens.
- Rule refs: §J0L (API compatibility), §J0T (secure defaults).

### T-005: Insecure Database Configuration (MEDIUM)

**Affected file:** config/database.php

- DB credentials hardcoded in source (host, user, password).
- Empty root password.
- No `.env.example` template.
- File is in web-accessible path (`config/` is at project root).

**Mitigation required:**
- Move credentials to environment variables.
- Add `.env.example` template.
- Ensure `config/` is not web-accessible (add `.htaccess` deny rule).
- Rule refs: §J6 (secrets), §J0T (secure defaults), §J0P (environment drift).

### T-006: No Rate Limiting (LOW-MEDIUM)

**Issue:** No rate limiting on login attempts.

**Impact:** Brute-force password attacks.

**Mitigation:** Add rate limiting on login endpoint.
- Rule ref: §J0T (rate limiting/abuse prevention).

### T-007: Session Management (LOW)

**Issue:** No session timeouts, no session regeneration after login.

**Impact:** Session fixation, prolonged session validity.

**Mitigation:**
- `session_regenerate_id(true)` after successful login.
- Set session cookie flags: `HttpOnly`, `Secure`, `SameSite=Strict`.
- Configure session timeout.
- Rule refs: §J0T (secure cookie/session settings), §J15D (observability).

## 4. Mitigations Already in Place

| Mitigation | Files | Effectiveness |
|-----------|-------|---------------|
| `htmlspecialchars()` on output | All pages | Prevents reflected XSS in dynamic content |
| `user_id` scoping in queries | All pages | Prevents IDOR (query-level) |
| Login check on protected pages | dashboard, subjects, etc. | Prevents unauthorized page access |
| Prepared statements | subjects.php, studyplanner.php | Prevents SQLi in those files |
| Delete confirmation dialogs | All CRUD pages | UX safety (not security) |

## 5. Verification Plan (Level 5 — security)

Pending TESTING gate approval:
- [ ] Run `php -l` on all PHP files (static analysis)
- [ ] Run SQLi test inputs against all form endpoints
- [ ] Verify CSRF token implementation
- [ ] Audit password storage (verify `password_hash`/`password_verify`)
- [ ] Verify rate limiting on login
- [ ] Verify session cookie flags
- [ ] Re-run OWASP ZAP or equivalent scanner

## 6. Risk Acceptance

The identified vulnerabilities are CRITICAL (SQLi, plaintext passwords). They
must be fixed before any release to production. The TESTING gate (`"run tests"`)
is required to unlock these fixes per §J1 (protection phase).
