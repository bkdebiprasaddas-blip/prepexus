# IMPL-SPEC.md — Implementation Specification (As-Built)

> **Stack:** PHP 8.5.8 (cli) / PHP 8.2.12 (Apache), MariaDB 10.4.32, vanilla JS/HTML/CSS.
> **Gate:** CODING (complete). No code was written or modified in this session.

## 1. Conventions

### 1.1 Authentication & Authorization
- Session-based via `session_start()` + `$_SESSION['user_id']`.
- Each protected page (dashboard, subjects, studyplanner, materials, progress,
  profile) checks `isset($_SESSION['user_id'])` at the top and redirects to
  `login.php` if absent.
- `logout.php` calls `session_unset()` + `session_destroy()` then redirects.
- Single role: student/user. No admin or tier system.
- User identity (`user_id`, `user_name`) stored in session after login.

### 1.2 Database Access
- `config/database.php` creates a single `$conn` (mysqli) connection.
- Two patterns coexist in the codebase:
  - **Prepared statements** (secure): `subjects.php`, `studyplanner.php`
    use `mysqli_prepare` + `mysqli_stmt_bind_param`.
  - **String interpolation** (vulnerable — see SECURITY-THREAT-MODEL.md):
    `login.php`, `register.php`, `materials.php`, `profile.php`, `dashboard.php`,
    `progress.php` interpolate variables directly into SQL strings.
- All queries are scoped to the current user via `user_id` from `$_SESSION`.

### 1.3 Output Escaping
- Dynamic content in HTML uses `htmlspecialchars()` for user-supplied values
  (names, emails, descriptions, titles, etc.).
- Some values use `strtoupper(substr(..., 0, 1))` for avatar initials.
- Date values use `date("d M Y", strtotime(...))` for display formatting.

### 1.4 Error Handling
- `mysqli_connect_errno()` / `die()` in database.php for connection failures.
- `mysqli_query` results are not checked for errors in most files (no
  `mysqli_error` calls beyond connection).
- Form processing redirects via `header("Location: ...")` after success.
- No try/catch or exception handling anywhere in the codebase.

### 1.5 Input Handling
- HTML5 form validation (`required`, `type="email"`, `type="url"`,
  `type="date"`, `type="number"` min attributes).
- Server-side: `trim()` applied to text fields in subjects.php and studyplanner.php.
- No `filter_var()` or explicit server-side validation in login/register.
- `$_GET` parameters used for: `edit`, `delete`, `complete`, and query-param
  success/error messages (`?success=1`, `?updated=1`, `?deleted=1`).

### 1.6 Redirect Pattern
- POST → process → `header("Location: page.php?success=1")` → redirect.
- This prevents form resubmission on refresh (except materials.php which
  uses a slightly different flow).

## 2. Per-File Behavior

### index.php (Landing Page)
- Public (no auth check).
- Includes `config/database.php` (not actually used for queries here).
- Includes `includes/navbar.php` (Home/Login/Register links).
- Static hero section with feature cards, feature list, CTA buttons.
- Inline `<style>` block for feature card CSS.
- Base URL: `/prepexus/` (hardcoded in asset paths).

### login.php (Authentication)
- Public page. Starts session.
- Accepts POST: `email`, `password`.
- Query: `SELECT * FROM users WHERE email=[input] AND password=[input]` (string interpolation — VULNERABLE)
  (VULNERABLE: SQL injection + plaintext comparison).
- On success: sets `$_SESSION['user_id']`, `$_SESSION['user_name']`,
  redirects to `dashboard.php`.
- On failure: shows "Invalid email or password." message.
- Includes navbar.php.

### register.php (Registration)
- Public page. Starts session (implicitly via include).
- Accepts POST: `name`, `email`, `password`, `course`, `semester`.
- Checks for duplicate email: `SELECT * FROM users WHERE email='$email'`.
- Inserts new user: `INSERT INTO users (name, email, password, course,
  semester) VALUES ('$name', '$email', '$password', '$course', '$semester')`
  (VULNERABLE: SQL injection, plaintext password).
- Shows success/failure message.
- Includes navbar.php.
- Course/semester dropdown for semester (1-6).

### logout.php (Session Termination)
- Starts session.
- `session_unset()`, `session_destroy()`.
- Redirects to `login.php`.
- No HTML output.

### dashboard.php (Dashboard)
- Protected (auth check).
- Queries: count subjects, count tasks, count materials, count completed
  tasks, count pending tasks, count in-progress tasks (all using string
  interpolation — VULNERABLE).
- Calculates: `overall_progress = round((completed / total) * 100)`.
- Renders: dashboard navbar (logo + user avatar/name), collapsible sidebar
  (7 menu items), statistics cards (4 cards), progress card with progress
  bar, task status breakdown, quick access cards (4 cards).
- Inline `<script>`: `toggleSidebar()` function.

### subjects.php (Subject Management)
- Protected.
- **Uses prepared statements** (secure pattern).
- CRUD operations:
  - Add: `INSERT INTO subjects` via `mysqli_prepare` + `bind_param("issis", ...)`.
  - Update: `UPDATE subjects SET ... WHERE id = ? AND user_id = ?`.
  - Delete: `DELETE FROM subjects WHERE id = ? AND user_id = ?` (via GET
    — CSRF risk).
  - Edit: `SELECT ... FROM subjects WHERE id = ? AND user_id = ?`.
  - List: `SELECT ... FROM subjects WHERE user_id = ? ORDER BY id DESC`.
- Renders: dashboard navbar, sidebar (Subjects active), back button,
  page header, success/error messages, add/edit form (inline), subject
  table with edit/delete actions, empty state.

### studyplanner.php (Study Task Planner)
- Protected.
- **Uses prepared statements** (secure pattern).
- CRUD operations:
  - Add: `INSERT INTO tasks` via `mysqli_prepare` + `bind_param("iissssss", ...)`.
  - Update: `UPDATE tasks SET ... WHERE id = ? AND user_id = ?`.
  - Delete: `DELETE FROM tasks WHERE id = ? AND user_id = ?` (GET — CSRF risk).
  - Complete: `UPDATE tasks SET status = 'Completed' WHERE id = ? AND user_id = ?`.
  - Edit: `SELECT ... FROM tasks WHERE id = ? AND user_id = ?`.
  - List: `SELECT tasks.*, subjects.subject_name FROM tasks INNER JOIN subjects ...
    WHERE tasks.user_id = ? ORDER BY tasks.due_date ASC, tasks.id DESC`.
  - Subjects for dropdown: `SELECT id, subject_name FROM subjects WHERE user_id = ?`.
- Renders: dashboard navbar, sidebar (Study Planner active), back button,
  page header, success messages, add/edit task form, task table with
  priority/status badges, complete/delete actions, empty state.
- Priority options: High, Medium, Low.
- Status options: Pending, In Progress, Completed.

### materials.php (Study Materials)
- Protected.
- **VULNERABLE:** Uses string interpolation for all queries.
- CRUD operations:
  - Add: `INSERT INTO materials (...) VALUES (...)` (string interpolation).
  - Update: `UPDATE materials SET ... WHERE id='$id' AND user_id='$user_id'`.
  - Delete: `DELETE FROM materials WHERE id='$id' AND user_id='$user_id'` (GET — CSRF risk).
  - Edit: `SELECT * FROM materials WHERE id='$id' AND user_id='$user_id'`.
  - Subjects dropdown: `SELECT * FROM subjects WHERE user_id='$user_id'`.
  - List: `SELECT materials.*, subjects.subject_name FROM materials JOIN subjects ...
    WHERE materials.user_id='$user_id' ORDER BY materials.id DESC`.
- Renders: dashboard navbar, sidebar (Materials active), back button, page
  header, success messages, add/edit form, materials table with Open/Edit/Delete
  actions, empty state.
- Material types: PDF, Video, Website, Notes.
- Uses relative asset paths (`css/style.css`, `images/prepxus.png`) — inconsistent
  with other pages that use `/prepexus/css/style.css`.

### progress.php (Progress Tracker)
- Protected.
- **VULNERABLE:** Uses string interpolation for count queries.
- Queries: total tasks, completed tasks, pending tasks, in-progress tasks,
  all subjects.
- Calculates: `overall_progress = round((completed / total) * 100)`.
- Per-subject progress: for each subject, count total tasks and completed tasks,
  calculate percentage.
- Renders: dashboard navbar, sidebar (Progress active), back button, page
  header, overall progress card with progress bar, statistics cards (4),
  subject-wise progress list with progress bars.

### profile.php (Profile Management)
- Protected.
- **VULNERABLE:** Uses string interpolation for queries.
- Update: `UPDATE users SET name='$name', email='$email', course='$course',
  semester='$semester' WHERE id='$user_id'`.
- Load: `SELECT * FROM users WHERE id='$user_id'`.
- Updates `$_SESSION['user_name']` after profile update.
- Renders: dashboard navbar, sidebar (Profile active), back button, page
  header, profile card (avatar + name/email display), edit form with
  name/email/course/semester fields, save button.

## 3. Shared Components

### Includes
- `config/database.php` — DB connection (included by all pages).
- `includes/navbar.php` — Top navbar with logo + Home/Login/Register links
  (only used on index.php, login.php, register.php).

### CSS
- `css/style.css` — Single global stylesheet (65 KB), covers all pages.
- `index.php` has an inline `<style>` block for feature card-specific CSS.

### JavaScript
- Each protected page has an inline `<script>` with `toggleSidebar()`.
- subjects.php: also `showForm()`, `hideForm()`.
- studyplanner.php: also `showTaskForm()`, `hideTaskForm()`.
- materials.php: also `showMaterialForm()`, `hideMaterialForm()`.

### Asset Path Inconsistency
- `index.php`, `login.php`, `register.php`, `dashboard.php`, `profile.php`:
  use `/prepexus/css/style.css` (absolute path).
- `subjects.php`, `studyplanner.php`, `progress.php`: use `css/style.css`
  (relative path).
- `materials.php`: uses `css/style.css` (relative path).
- All pages reference `/prepexus/images/prepxus.png` or `images/prepxus.png`.

## 4. Module Behaviors

### Navigation
- Public pages (index, login, register): navbar with Home/Login/Register.
- Protected pages: dashboard-style navbar (logo + user avatar/name) +
  collapsible sidebar with 6 menu items (Dashboard, Subjects, Study Planner,
  Materials, Progress, Profile) + Logout.
- Back buttons on all protected pages link to dashboard.php.

### Sidebar Toggle
- `toggleSidebar()` toggles `.open` class on sidebar and `.show` class on
  overlay. Implemented via inline JS on each page (duplicate code).
- `☰` menu button opens sidebar; `×` button and overlay close it.

## 5. Acceptance Criteria (existing behavior)

- User can register with unique email.
- User can log in with email + password.
- Authenticated user sees dashboard with correct stats.
- User can CRUD subjects (scoped to their account).
- User can CRUD study tasks (scoped to their account, linked to subjects).
- User can CRUD study materials (scoped to their account, linked to subjects).
- User can view progress (overall + per-subject).
- User can update profile (name, email, course, semester).
- User can log out and session is destroyed.

## 6. Verification

- No automated tests exist. Verification is manual only.
- `php -l` (lint) not run on any file yet.
- See SETUP-GUIDE.md §4 for manual testing steps.
