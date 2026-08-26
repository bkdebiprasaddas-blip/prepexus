# DB-DESIGN.md — Database Design (As-Built)

> **Environment:** MariaDB 10.4.32, PHP 8.5.8 (cli).
> **Source of truth:** `prepexus.sql` (schema dump) and `config/database.php`.

## 1. Connection Configuration

File: `config/database.php`

- Host: `localhost`
- Username: `root`
- Password: (empty)
- Database: `prepexus`
- Driver: `mysqli` (procedural API)
- Connection variable: `$conn`

**Security note:** Credentials are stored in plaintext in a root-level config
file with no environment variable abstraction. This is acceptable for local
XAMPP development but must be changed for production (document in TODO.md
backlog — §J6).

## 2. Schema Overview

Four tables, all keyed by `user_id` (single-tenant-per-user model):

| Table | Purpose |
|-------|---------|
| `users` | User accounts |
| `subjects` | Academic subjects per user |
| `tasks` | Study tasks per subject |
| `materials` | Study materials per subject |

## 3. Table Definitions

### 3.1 `users`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PRIMARY KEY, AUTO_INCREMENT | User ID |
| name | varchar(100) | NOT NULL | Full name |
| email | varchar(100) | NOT NULL | Email (used for login) |
| password | varchar(255) | NOT NULL | **Stored in plaintext** — REDACTED seed value (see SECURITY-THREAT-MODEL.md §T-002) |
| course | varchar(50) | NOT NULL | e.g., "BCA" |
| semester | varchar(20) | NOT NULL | e.g., "6th Semester" |

### 3.2 `subjects`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PRIMARY KEY, AUTO_INCREMENT | Subject ID |
| user_id | int(11) | NOT NULL, KEY | FK → users(id) |
| subject_name | varchar(100) | NOT NULL | e.g., "Web Frame Services" |
| subject_code | varchar(30) | NOT NULL | e.g., "WFS" |
| credit | int(11) | NOT NULL | Credit hours (e.g., 4) |
| description | varchar(255) | NOT NULL | Short description |

### 3.3 `tasks`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PRIMARY KEY, AUTO_INCREMENT | Task ID |
| user_id | int(11) | NOT NULL, KEY | FK → users(id) |
| subject_id | int(11) | NOT NULL, KEY | FK → subjects(id) |
| title | varchar(150) | NOT NULL | Task title |
| description | varchar(255) | NOT NULL | Task description |
| study_date | date | NOT NULL | When to study |
| due_date | date | NOT NULL | Deadline |
| priority | varchar(20) | NOT NULL | High, Medium, Low |
| status | varchar(20) | NOT NULL | Pending, In Progress, Completed |

### 3.4 `materials`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PRIMARY KEY, AUTO_INCREMENT | Material ID |
| user_id | int(11) | NOT NULL, KEY | FK → users(id) |
| subject_id | int(11) | NOT NULL, KEY | FK → subjects(id) |
| material_name | varchar(150) | NOT NULL | e.g., "PHP complete notes" |
| material_type | varchar(30) | NOT NULL | PDF, Video, Website, Notes |
| material_link | varchar(255) | NOT NULL | URL to material |
| description | varchar(255) | NOT NULL | Short description |

## 4. Relations & Constraints

```
users ──< subjects     (user_id → users.id)
users ──< tasks        (user_id → users.id)
users ──< materials    (user_id → users.id)
subjects ──< tasks     (subject_id → subjects.id)
subjects ──< materials (subject_id → subjects.id)
```

All foreign keys are indexed (`KEY user_id`, `KEY subject_id`).

**Note:** Foreign key enforcement is declared in the schema but depends on
InnoDB engine and MariaDB configuration. The application-level code enforces
user_id scoping on every query (defense in depth for IDOR prevention — see §4.3).

## 5. Index Summary

| Table | Indexes |
|-------|---------|
| users | PRIMARY KEY (id) |
| subjects | PRIMARY KEY (id), KEY (user_id) |
| tasks | PRIMARY KEY (id), KEY (user_id), KEY (subject_id) |
| materials | PRIMARY KEY (id), KEY (user_id), KEY (subject_id) |

**Missing indexes (backlog):** Consider composite index on
`tasks (user_id, status)` for dashboard progress queries. See TODO.md §4.1.

## 6. Seed Data

The SQL dump includes sample data for user_id=1:
- 9 subjects (Web Frame Services, Advance Web Design, .NET Technology, etc.)
- 3 tasks (2 units, All 4 units, Dcumantation, Project)
- 4 materials (PHP notes, .NET video, AWD PDF, LOS website)
- 1 user (Kamal Padhi, email: padhikam13@gmail.com)

**Security note:** The seed user's password is stored in plaintext in the SQL
dump. It is REDACTED here per §J6 (no credentials in planning docs). This seed
data must not appear in any production deployment.

## 7. Key Calculations

- **Overall progress** = `round((completed_tasks / total_tasks) * 100)`
- **Subject progress** = `round((subject_completed / subject_total) * 100)`
- **Task counts by status:** Completed, Pending, In Progress

## 8. Security Issues

### 4.1 Plaintext password storage
- `register.php:23` inserts password directly: `VALUES (... '$password' ...)`
- `login.php:14` compares plaintext: query checks `WHERE email=[input] AND password=[input]` (vulnerable to SQLi)
- **Fix:** Use `password_hash()` on registration, `password_verify()` on login.
- **Rule refs:** §J0T (secure defaults), §J15C (data lifecycle/privacy)

### 4.2 SQL injection (non-prepared queries)
- `login.php:14` — string interpolation in query
- `register.php:15,23` — string interpolation
- `materials.php:35,69,96,129` — string interpolation
- `profile.php:35,64` — string interpolation
- `dashboard.php`, `progress.php` — string interpolation in count queries
- **Already safe:** `subjects.php` and `studyplanner.php` use `mysqli_prepare` + `bind_param`
- **Fix:** Convert all remaining queries to prepared statements.

### 4.3 IDOR / object-level authorization
- All queries include `AND user_id='$user_id'` (good practice) — but the
  `user_id` comes from `$_GET['delete']` / `$_GET['edit']` parameters in some
  cases, which could allow IDOR if session checks were bypassed.
- **Fix:** Audit all parameter handling; ensure user_id always comes from
  `$_SESSION`, never from user input.
- **Rule ref:** §J0M (object-level authorization)

## 9. Acceptance Notes

- Database is importable via phpMyAdmin or `mysql -u root -p prepexus < prepexus.sql`
- All page-level queries are scoped to `$_SESSION['user_id']`
- CRUD operations on subjects and tasks use prepared statements (subjects.php, studyplanner.php)
