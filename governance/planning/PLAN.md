# PLAN.md — PREPEXUS Planning Document

> **Spec status:** As-built documentation (existing code mapped to rule book).
> **Environment versions:** PHP 8.5.8 (cli), MariaDB 10.4.32, Git 2.55.0.
> **Gate:** CODING (complete) → TESTING (pending).

## 1. Scope

**Product:** PREPEXUS — a web-based Student Study Tracker. Helps students organize
subjects, plan study tasks, manage study materials, and track progress.

**MVP features (existing):**
- User registration and login (session-based)
- Landing page (index.php)
- Dashboard with statistics cards (dashboard.php)
- Subject management: add, edit, delete, list (subjects.php)
- Study task planner: add, edit, delete, mark complete (studyplanner.php)
- Study materials: add, edit, delete, open links (materials.php)
- Progress tracker: overall + per-subject (progress.php)
- Profile management: update name, email, course, semester (profile.php)
- Logout (logout.php)

**Non-goals (backlog):**
- Password hashing (currently plaintext — see SECURITY-THREAT-MODEL.md)
- CSRF protection (not implemented)
- Multi-role / admin panel (single-role student/user only)
- Email verification
- Mobile app
- External API integration
- Automated test suite (manual testing only currently)

## 2. Stack and Rationale

| Layer | Technology | Rationale |
|-------|-----------|-----------|
| Runtime | PHP 8.5.8 (cli) / PHP 8.2.12 (Apache) | Already the established runtime; minimal changes preferred (§J7) |
| Database | MariaDB 10.4.32 | Already in use via XAMPP; matches existing schema |
| Web server | Apache (XAMPP) | Already configured and serving the app |
| Frontend | HTML5, CSS3, vanilla JS | Already implemented; no build tooling needed |
| DB access | mysqli (procedural) | Already used throughout all PHP files |
| Version control | Git 2.55.0 | Available but not yet initialized |

## 3. Architecture

**Single-file PHP pattern:** Each page is a standalone `.php` file containing:
1. PHP logic (session check, DB queries, form processing) at the top
2. HTML markup with inline PHP echoes for dynamic content
3. Inline `<script>` for client-side interactivity (sidebar toggle, form show/hide)

**Shared resources:**
- `config/database.php` — mysqli connection (included by every page)
- `includes/navbar.php` — shared top navbar (landing page only)
- `css/style.css` — global stylesheet (65 KB, 2,600+ lines)
- `images/prepxus.png` — logo asset

**Auth model:** Session-based via `$_SESSION`. Each protected page checks
`isset($_SESSION['user_id'])` and redirects to `login.php` if absent. Single
role: student/user. No admin or tiered permissions.

## 4. File Map

```
prepexus-main/
├── index.php              Landing page (hero, features, CTA)
├── login.php              Login form + auth check
├── register.php           Registration form
├── logout.php             Session destroy + redirect
├── dashboard.php          Dashboard (stats cards, progress bar, quick access)
├── subjects.php           Subject CRUD (add/edit/delete/list)
├── studyplanner.php       Task CRUD + mark complete (add/edit/delete/list)
├── materials.php          Material CRUD (add/edit/delete/open link)
├── progress.php           Progress visualization (overall + per-subject)
├── profile.php            Profile update (name/email/course/semester)
├── config/
│   └── database.php       mysqli connection config
├── includes/
│   └── navbar.php         Shared navbar partial
├── css/
│   └── style.css          Global stylesheet
├── images/
│   └── prepxus.png        Logo (848 KB)
├── prepexus.sql           Database schema + seed data
├── README.md              Project README
├── .gitignore             Updated (ignores Scratch/, .env, build artifacts)
├── AGENTS.md              Rule book (§A/§D/§F/§H/§J verbatim + project appendix)
├── governance/            Continuity records (tracked in git)
│   ├── RULEBOOK.md        Master rule book
│   ├── BOOTSTRAP.md       Current-state snapshot
│   ├── ai-context/        Session logs
│   ├── work-log/          Daily logs
│   ├── planning/          Specs + plans
│   └── documentation/     Setup guide
└── Scratch/               Disposable prep (git-ignored)
```

## 5. Workflow

1. User registers or logs in.
2. Authenticated users access dashboard.php.
3. From dashboard, navigate to Subjects / Study Planner / Materials /
   Progress / Profile via sidebar.
4. Each module follows CRUD + redirect pattern (POST → process → redirect to
   same page with query param for success/error feedback).
5. Logout destroys the session.

## 6. Progress Rules

- **Current gate:** CODING (complete). Code exists and is functional.
- **Next gate:** TESTING — requires `"run tests"` approval.
- **Security fixes** (password hashing, SQL injection, CSRF) are blocked
  during the protection phase (§J1) until TESTING is approved.
- **UI is already implemented** — no UI DESIGN gate needed (code exists).
- **Release** requires `"approve release"` after testing passes.

## 7. Sample Outputs

- **Dashboard:** Shows 4 stat cards (Subjects, Study Tasks, Materials, Overall
  Progress %) + progress bar + task status breakdown + quick-access cards.
- **Subjects table:** Lists subjects with inline-edit form, showing No/Subject/Code/Credit/Description/Action.
- **Study Planner:** Task table with status badges (Pending/In Progress/Completed), priority badges, complete/delete actions.
- **Progress:** Overall progress bar + per-subject progress bars with percentages.
- **Profile:** Avatar (initial) + form to edit name, email, course, semester.
