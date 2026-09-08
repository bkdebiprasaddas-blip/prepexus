# Deep-Dive Documentation for PREPEXUS

## 1. Executive Summary

PREPEXUS is a single-file PHP student study tracker application designed to monitor study goals, materials, and progress over time. It runs on a simple LAMP-like stack (PHP 8.5.8, MariaDB 10.4.32) with HTML5/JS frontend and offers a clean white-on-black inspired design.

**Core Feature Snapshot:**
- Central dashboard displaying current goals
- Subject-based study management
- Planner for month-by-month study plans
- Materials repository for study resources
- Visual progress tracking per subject and week
- User profile management
- Session-based authentication

**Rapid Startup (no Composer, no npm, no build steps):**
```bash
# 1. Ensure environment (Windows + XAMPP + PHP 8.5.8 + MariaDB)
# 2. Copy prepexus.sql to MariaDB
mysql -u root -p < database/prepus.sql

# 3. Update database.php with your credentials
# 4. Serve files via Apache htaccess rewrite rules
# 5. Open browser to index.php and register/login
```

## 2. Project at a Glance

**Domain:** Education / Student Productivity
**Scope:** Student study tracker (single role, no team)
**Format:** Single-file PHP + wired HTML/JS frontend (no framework or MVC)
**Architecture:** Single-server, no API gateway, no microservices

**Live Endpoints (Perceived via paths):**
- `/index.php` — landing page, redirects to dashboard
- `/login.php` — credential-based login form
- `/register.php` — new user registration
- `/dashboard.php` — root view of goals and progress
- `/subjects.php` — view/manage subjects
- `/studyplanner.php` — weekly study schedules
- `/materials.php` — repository of study resources
- `/progress.php` — detailed progress tracking
- `/profile.php` — user profile management
- `/logout.php` — session termination

```
┌───────────────────────────────────────────────────────────────────────┐
│  Title: PREPEXUS - Student Study Tracker                                 │
│  Tagline: Be intentional about your education                            │
│  Tag color: white badge, dark text                                      │
│  Hero: "You are 8 days into your dream, you will get there."            │
│  Color Identity: White coating with black base, white text ("light")    │
│  Accent: white shape list bars                                            │
│                                                                          │
│  Theme: dark, pure black, white                                           │
│  Font Stack: system-ui, -apple-system, BlinkMacSystemFont                │
│  Styling: style.css (68 KB), no external scripts, no build steps         │
│                                                                          │
│  UX Patterns: Clean form labels, right controls alignment,               │
│               placeholder dates (25-digit MM/DD/YYYY),                  │
│               circular/moon-week iconography with white list items      │
└───────────────────────────────────────────────────────────────────────┘
```

## 3. Repository Snapshot (Delta only)

**Files Changed (Working Branch):**
- Modified: All governance/ files, LEADING to a completed Project Plan
- Other: Scratch/private files are git-ignored, not relevant

**Known Production Files:**
- 9 root files (index.php, login.php, register.php, dashboard.php, subjects.php, studyplanner.php, materials.php, progress.php, profile.php, logout.php)
- config/database.php (connection)
- css/style.css (68 KB unminified)
- includes/navbar.php (navigation helper)
- images/prepxus.png (brand asset)
- governance/planning/...pending
- governance/documentation/SETUP-GUIDE.md (orphan page)

**Known Gov Files (Current State):**
- AGENTS.md — active rule book (40 KB)
- RULEBOOK.md — governance charter (93 KB)
- BOOTSTRAP.md — session snapshot (2.4 KB)
- ai-context/SESSION-2026-08-24-1 .. archive/SESSION-2026-08-25-1 (3 logs)
- work-log/LOG-2026-08-24 .. LOG-2026-08-26 (3 daily logs)

**Scratch/ is NOT tracked (git-ignored).**

## 4. File Inventory

**Root / Production Layer**
```
index.php
  ├─ Layout: navbar.php inclusion
  ├─ Behavior: Redirects to dashboard.php on load
  └─ Status: ok

login.php
  ├─ Layout: Form <form method="POST"> with fields:
  │   ├─ username (text)
  │   └─ password (password)
  ├─ Behavior: POST to itself; validate POST; on success,
  │   fetch user from DB, create session uid, redirect to dashboard.php
  ├─ Validation: empty username/password check
  ├─ Session: uid=session['uid']
  └─ Security: nil; credential check only

register.php
  ├─ Layout: Form <form method="POST"> with fields:
  │   ├─ username (text)
  │   └─ password (password)
  ├─ Behavior: POST to itself; validate POST; hash password; insert
  │   new user; on success, redirect to login.php
  ├─ Validation: empty username/password check
  ├️ Security: nil; plaintext password stored (SECURITY THREAT-MODEL.md notes this)
  └─ Status: ok,-un hardened for auth

dashboard.php
  ├─ Layout: Navbar top, welcome + logout link, list of goals
  ├─ Behavior: Selects ABS(ceil(datediff)?/7) weeks ago; loop through weeks
  │   0..6; foreach week: GET goal_count per user-week; if >0,
  │     get goal_id list; select subject_name, title, goal_id, minute_count
  │     via group_concat; render white list items per week
  ├️ Security: Nil (no CSRF, no SQL injection controls; reliant on trust)
  └️ Query behavior: Risky GROUP_CONCAT + SELECT * (need NUMERIC, no exposure)

subjects.php
  ├─ Layout: Form <form> + white list for list-items
  ├─ Behavior: POST to itself; validate POST; INSERT new subject
  │   with {subject_name, user_id}; on success, SELECT subject_name for
  │   user and render list
  ├️ Security: Nil (no CSRF, no SQL injection controls; reliant on trust)
  └️ Query behavior: Risky SELECT * (need NUMERIC, no exposure)

studyplanner.php
  ├─ Layout: Navbar + table for 4 cells (week1..week4)
  ├─ Behavior: GET by default; select week param; loop weeks 0..3
  │   SELECT weekly goals from prep_study; GROUP_CONCAT weekly goals as
  │   a comma-separated string with time format H:mm; render table
  ├️ Security: Nil (no CSRF or SQL injection controls)
  └️ Query behavior: Risky GROUP_CONCAT + SELECT * (need NUMERIC, no exposure)

materials.php
  ├─ Layout: Navbar + form + white list
  ├─ Behavior:
  │   - GET: SELECT learning_material from prep_learning_material where
  │     user_id = session['uid']; render list
  │   - POST (adds): validate POST; INSERT {material_name, material_link,
  │     learning_material, user_id} into prep_learning_material; redirect
  │     to self
  ├️ Security: Nil (no CSRF, no SQL injection controls)
  └️ Query behavior: Risky SELECT * (need NUMERIC, no exposure)

progress.php
  ├─ Layout: Navbar + form + white list with progress per subject
  ├─ Behavior:
  │   - GET: SELECT subject_id from prep_subjects for user; foreach subject
  │     SELECT goal_count from prep_user_week for (user, subject_id);
  │     compute progress = goal_count / subject_minutes; render with
  │     progress %, width %, rounded counts
  │   - POST (updates): validate POST; compute completed = goal_count,
  │     pending = subject_minutes - completed; INSERT/UPDATE
  │     prep_user_week with (user, subject_id, week) + completed;
  │     no pending field in DB (in-memory)
  ├️ Security: Nil (no CSRF, no SQL injection controls)
  └️ Query behavior: Risky SELECT * + multiple SUMs GROUP BY (need NUMERIC)

profile.php
  ├─ Layout: Navbar + welcome msg + logout link
  ├─ Behavior: Unconditional logout; session_destroy(); header('/');
  └️ Security: Nil (no auth guard)

logout.php
  ├─ Layout: Cleanup session; redirect to '/'
  └─ Security: Nil (no auth guard)
```

**Includes / Shared**
```
includes/navbar.php
  ├─ Layout: <nav> > details: brand "PREPEXUS" > white badge
  ├─ Content: breadcrumb path "/" + title "Be intentional about your
  │            education", nav links to subjects (dashboard, subjects,
  │            studyplanner, materials, progress, profile)
  └─ Style: <span class="badge">->text-white</span> -> white text
```

**Config**
```
config/database.php
  └─ Behavior: string ConnectString using mysqli("localhost", "root",
  │    "", "prepexus_db"); returns $conn
```

**Multi-file set**
```
database/prepus.sql (SQL setup)
  ├─ Setup scripts include:
  │   - Database prepexus_db
  │   - Users: prep_users (id, username, password[type], user_id)
  │   - Subjects: prep_subjects (id, subject_name, user_id, subject_minutes)
  │   - User-week tracking: prep_user_week (id, user_id, subject_id, week,
  │     goal_count)
  │   - Materials: prep_learning_material (id, material_name, material_link,
  │     learning_material, user_id)
  │   - Weekly goals: prep_study (id, user_id, subject_id, week, month,
  │     title, hour, minute, day, monthcount, date)
  │   - Insert seed: 1 row in each table (seed id: 1)
  └─ Dependencies: mysql server, root user no-password (rulebook section C
    notes undermine security, to be formally scoped and reviewed)

css/style.css (CSS)
  └─ Isolation: stylesheets loaded in <head>; root defaults body { margin:
    0px; background: black; color: white; font-family: system-ui, -apple-
    system, BlinkMacSystemFont }
```

**Public Assets**
```
images/png
  └─ images/large/prepxus.png (848 KB brand logo PNG)
```

**Governance / Continuity (Snapshot)**
```
governance/AGENTS.md — root rule book (40 KB)
governance/RULEBOOK.md — governance charter (93 KB)
governance/BOOTSTRAP.md — current state (2.4 KB)
governance/ai-context/
  ├─ SESSION-2026-08-26-1.md, 2026-08-26-2.md, 2026-08-26-3.md (3 logs)
  └─ archive/SESSION-2026-08-24-1.md, SESSION-2026-08-25-1.md (archived)
governance/work-log/
  └─ LOG-2026-08-24.md, LOG-2026-08-25.md, LOG-2026-08-26.md (3 daily logs)
```

**Scratch / Git-ignored staging environment**
```
Scratch/__pycache__/
└─ Scratch/prepexus/coding/.gitkeep (empty, source decoration)
└─ Scratch/prepexus/debugging/.gitkeep (empty, source decoration)
└─ Scratch/prepexus/suggestions/.gitkeep (empty, source decoration)
└─ Scratch/prepexus/coding/assets/.gitkeep (empty, source decoration)
```

**Readme / Meta**
```
README.md — project description (1.29 KB)
```

**Other**
- Governance/planning/* — Pending: PLAN.md, DBDESIGN.md, IMPLSPEC.md,
  UI_SPEC.md, TODO.md, RELEASE-PLAN.md, SECURITY-THREAT-MODEL.md (docs exist
  but sections incomplete)
- Governance/documentation/SETUP-GUIDE.md — Outdated info (mentions Composer,
  Vue, Vite, Babel; not applicable to current single-file PHP + XAMPP stack)