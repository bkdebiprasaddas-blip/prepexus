# RELEASE-PLAN.md

> **Rule ref:** §E.8 (REQUIRED before production release), §J16 (Deployment gate), §J16A (Post-release verification).

## 1. Environments

| Environment | Purpose | DB | Deploy |
|-------------|---------|-----|--------|
| Local (dev) | XAMPP on developer machine | MariaDB 10.4.32 (prepexus) | Direct file edits |
| CI (pending) | Automated testing | Test DB instance | Manual trigger |
| Staging (pending) | Pre-production testing | Staging DB copy | Manual |
| Production (pending) | Live users | MariaDB 10.4.32 | TBD |

**Current state:** Only local dev environment exists. CI/staging/production
not yet configured.

## 2. Build / Deploy

**Current (local dev):** No build step. PHP files served directly by Apache.
Files deployed by copying to `C:\xampp\htdocs\prepexus-main\`.

**Production (to be defined):** Requires:
- Production web server configuration (Apache/Nginx)
- PHP OPcache enabled
- Database migration scripts (if schema changes)
- Environment variable configuration (`.env` for DB credentials, secrets)
- HTTPS/TLS configuration
- Error log configuration (off in production, to file)

## 3. Database Migrations

**Current schema:** `prepexus.sql` contains the full schema + seed data.
No migration framework in use (single-file PHP + mysqli).

**Migration approach (to be defined):**
- Use SQL patch files in a `migrations/` directory (e.g., `001_initial_schema.sql`).
- Apply with `mysql -u root -p prepexus < migrations/001_initial_schema.sql`.
- All migrations must be additive (no destructive drops) until TESTING gate.

## 4. Secrets & Configuration

**Current:** DB credentials in `config/database.php` (hardcoded, plaintext).
**Required before release:**
- Move credentials to environment variables.
- Add `.env.example` template (committed to git).
- Ensure real `.env` is git-ignored.
- Verify `.gitignore` blocks `.env`, `.env.*`, `**/.env`, `**/.env.*`.
- §J6 compliance check before staging.

## 5. Monitoring / Observability

**Current:** No monitoring. No structured logging. No error tracking.

**Required before release:**
- [ ] Enable PHP error logging (to file, not display in production).
- [ ] Add application-level logging for: login attempts, CRUD operations,
      errors.
- [ ] Redact passwords and sensitive data from logs.
- [ ] Add health check endpoint (e.g., `health.php` returning DB status).
- Rule ref: §J15D (observability), §J0T (secret redaction in logs).

## 6. Backup & Recovery

**Current:** No backup procedure defined.

**Required before release:**
- [ ] Define backup frequency (daily recommended).
- [ ] Define RPO / RTO targets.
- [ ] Test restore procedure.
- [ ] Document backup command: `mysqldump -u root -p prepexus > backup.sql`
- Rule ref: §J0Q (disaster recovery), §J17 (backup awareness).

## 7. Rollback Procedure

**If release fails:**
1. Revert code files to previous version (git checkout if repo exists).
2. If DB schema changed: restore from pre-migration backup.
3. Verify site functionality.
4. Notify users if outage was user-facing.

**Rollback verification:** Test login, dashboard, and one CRUD operation
after rollback.

## 8. Smoke Tests (Critical User Journeys)

Before release, verify:
- [ ] User can register a new account.
- [ ] User can log in.
- [ ] Dashboard loads with correct stats.
- [ ] User can add/edit/delete a subject.
- [ ] User can add/edit/delete a study task.
- [ ] User can add/edit/delete a study material.
- [ ] Progress page calculates correctly.
- [ ] User can update profile.
- [ ] User can log out.

## 9. Release Checklist

- [ ] All Testing phase tasks complete (security fixes + tests pass).
- [ ] Security/threat model findings resolved or accepted by owner.
- [ ] Production configuration/secrets available (env vars, not in code).
- [ ] Database migrations reviewed and ordered.
- [ ] Backup/recovery procedure tested.
- [ ] Monitoring/alerts/health checks active.
- [ ] Rollback procedure tested.
- [ ] Critical user journeys have smoke tests.
- [ ] Release version/artifact identifiable.
- [ ] Privacy, data retention, third-party integrations configured.
- [ ] §J16 gate triggers: `"approve release"` → then deploy.

## 10. Post-Release Verification (§J16A)

After deployment:
- [ ] Verify app health (all pages load).
- [ ] Verify auth (login/logout works).
- [ ] Verify DB connectivity.
- [ ] Verify critical workflows (CRUD operations).
- [ ] Check error logs.
- [ ] Record result in session log.
