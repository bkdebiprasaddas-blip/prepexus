# SETUP-GUIDE.md — Install, Run, Configure, Test, Harden

> **Environment:** PHP 8.5.8 (cli), MariaDB 10.4.32, Apache (XAMPP), Git 2.55.0.
> **Rule ref:** §J15G (reproducibility), §J16 (deployment gate).

## 1. Prerequisites

| Tool | Required Version | Install Check |
|------|-----------------|---------------|
| PHP | >= 8.2 (cli) | `php -v` |
| Apache | via XAMPP | `xampp-control.exe` |
| MariaDB | 10.4.32 | `mysql --version` (or check XAMPP) |
| Git | 2.55.0 | `git --version` |

**Note:** On this machine, `mysql` CLI is not on PATH — the MariaDB server
runs as a Windows service via XAMPP. Use phpMyAdmin at `http://localhost/phpmyadmin`
or the MySQL CLI if available.

## 2. Installation

### Step 1: Clone or copy project
```
Place the project at: C:\xampp\htdocs\prepexus\
(or C:\xampp\htdocs\prepexus-main\ as currently configured)
```

### Step 2: Start XAMPP
1. Open XAMPP Control Panel.
2. Start **Apache** and **MySQL** modules.

### Step 3: Create database
Open `http://localhost/phpmyadmin` → Create database named `prepexus`.

### Step 4: Import schema
In phpMyAdmin:
1. Select the `prepexus` database.
2. Click **Import**.
3. Choose `prepexus.sql`.
4. Click **Go**.

Or via CLI:
```cmd
mysqldump is not available; use the MySQL CLI if installed:
mysql -u root -p prepexus < prepexus.sql
```

### Step 5: Verify database config
Open `config/database.php`:
- Host: `localhost`
- Username: `root`
- Password: (empty — default XAMPP)
- Database: `prepexus`

## 3. Run the Project

### Development
```
Start XAMPP → Apache + MySQL
URL: http://localhost/prepexus/  (or http://localhost/prepexus-main/)
```

### Pages
| Page | URL | Auth required? |
|------|-----|----------------|
| Landing | `/prepexus/index.php` | No |
| Login | `/prepexus/login.php` | No |
| Register | `/prepexus/register.php` | No |
| Dashboard | `/prepexus/dashboard.php` | Yes |
| Subjects | `/prepexus/subjects.php` | Yes |
| Study Planner | `/prepexus/studyplanner.php` | Yes |
| Materials | `/prepexus/materials.php` | Yes |
| Progress | `/prepexus/progress.php` | Yes |
| Profile | `/prepexus/profile.php` | Yes |
| Logout | `/prepexus/logout.php` | No (destroys session) |

## 4. Testing

### 4.1 Static Analysis (PHP Lint)
```cmd
php -l index.php
php -l login.php
php -l register.php
php -l dashboard.php
php -l subjects.php
php -l studyplanner.php
php -l materials.php
php -l progress.php
php -l profile.php
php -l logout.php
php -l config/database.php
```

### 4.2 Manual Test Checklist

**Auth flow:**
- [ ] Register a new user → should see "Registration successful!"
- [ ] Log in with the new user → should redirect to dashboard
- [ ] Log in with wrong credentials → should show error
- [ ] Try to access dashboard.php without login → should redirect to login.php
- [ ] Log out → should redirect to login.php

**Subjects CRUD:**
- [ ] Add a subject → appears in table
- [ ] Edit a subject → changes reflected
- [ ] Delete a subject → removed from table (confirm dialog)

**Study Planner:**
- [ ] Add a task → appears in table
- [ ] Mark task as completed → status changes
- [ ] Edit a task → changes reflected
- [ ] Delete a task → removed (confirm dialog)

**Materials:**
- [ ] Add a material → appears in table
- [ ] Open a material link → opens in new tab
- [ ] Edit a material → changes reflected
- [ ] Delete a material → removed (confirm dialog)

**Progress:**
- [ ] Verify overall progress % matches completed/total tasks
- [ ] Verify per-subject progress bars are accurate

**Profile:**
- [ ] Update name → reflected in dashboard navbar
- [ ] Update course/semester → reflected

## 5. Harden (pre-production)

**CRITICAL — must be fixed before release (waiting for TESTING gate approval):**

1. **Password hashing:** Replace plaintext storage with `password_hash()` /
   `password_verify()`. See SECURITY-THREAT-MODEL.md §T-002.
2. **SQL injection:** Convert all string-interpolated queries to prepared
   statements. See §T-001.
3. **CSRF protection:** Add tokens to all forms. See §T-003.
4. **Delete via GET → POST:** Convert all GET-based mutations. See §T-004.
5. **Secret management:** Move DB credentials to `.env`, add `.env.example`,
   verify `.gitignore`. See §T-005.
6. **Session hardening:** `session_regenerate_id`, cookie flags.
   See §T-007.
7. **Rate limiting:** Add login attempt throttling. See §T-006.
8. **Config security:** Deny web access to `config/` via `.htaccess`.

## 6. Troubleshooting

| Issue | Solution |
|-------|----------|
| "database not connected" | Check XAMPP MySQL is running; verify config/database.php credentials |
| Page shows blank | Check Apache error logs; run `php -l <file>` for syntax errors |
| CSS not loading | Verify path: some pages use `/prepexus/css/` (absolute), others use `css/` (relative) |
| Session not persisting | Check PHP session folder permissions (XAMPP default: `C:\xampp\php\tmp`) |

## 7. Git Setup (optional)

```cmd
cd C:\xampp\htdocs\prepexus-main
git init
git add .
git commit -m "Initial commit with governance scaffold"
```

**Pre-commit checklist (§J6):**
- Verify `.env` is git-ignored: `git check-ignore .env`
- Verify `Scratch/` is git-ignored: `git check-ignore Scratch/`
- Never commit `config/database.php` with real credentials (see note above).
