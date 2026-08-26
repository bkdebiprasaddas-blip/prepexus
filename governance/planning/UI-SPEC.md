# UI-SPEC.md — Visual Design Specification (As-Built)

> **Gate:** CODING (complete). UI is already implemented — no UI DESIGN gate
> needed. This documents the existing UI for reference.

## 1. Design System

### 1.1 Color Palette (extracted from style.css)

| Variable | Hex | Usage |
|----------|-----|-------|
| Primary | `#2f80ed` | Accent, links, icons |
| Dark text | `#182238` | Headings |
| Medium text | `#1c263b` | Cards |
| Muted text | `#71809a` | Descriptions, labels |
| Light border | `#e1e7f0` | Card borders |
| Light border 2 | `#edf0f5` | Section separators |
| Light bg | `#edf5ff` | Icon backgrounds (blue) |
| Light bg 2 | `#eefaf4` | Icon backgrounds (green) |
| Light bg 3 | `#f4efff` | Icon backgrounds (purple) |
| White | `#ffffff` | Card backgrounds |

### 1.2 Typography
- No custom fonts — uses system default.
- Heading sizes: h1 (~32px), h2 (~24px), h3 (~18px-22px).
- Small title labels: 11px, uppercase, letter-spacing 1.5px.
- Body: 13-15px, line-height 1.5.

### 1.3 Layout
- Responsive meta viewport tag on all pages.
- Dashboard pages: navbar (top) + collapsible sidebar (left) + main content (right).
- Sidebar width: collapsed by default on mobile, visible on desktop.
- Card-based layout with rounded corners (border-radius: 20px), box-shadow,
  and hover effects (transform: translateY(-6px)).

## 2. Component Inventory

| Component | Pages | Notes |
|-----------|-------|-------|
| Navbar (public) | index, login, register | Logo + Home/Login/Register |
| Navbar (dashboard) | dashboard, subjects, studyplanner, materials, progress, profile | Logo + user avatar/name |
| Sidebar | dashboard, subjects, studyplanner, materials, progress, profile | 7 items (6 pages + logout), collapsible |
| Sidebar overlay | same | Click to close |
| Hero section | index | Large heading + CTA buttons |
| Feature cards | index | 3 cards with icons |
| Stat cards | dashboard | 4 cards (Subjects, Tasks, Materials, Progress) |
| Progress bar | dashboard, progress | Animated width based on % |
| Quick access cards | dashboard | 4 cards linking to modules |
| Form modal/inline | subjects, studyplanner, materials, profile | Toggle show/hide |
| Data table | subjects, studyplanner, materials | With edit/delete actions |
| Status badges | studyplanner | Pending / In Progress / Completed |
| Priority badges | studyplanner | High / Medium / Low |
| Empty state | subjects, studyplanner, materials | Icon + message + CTA button |

## 3. Page-by-Page Checklist

### index.php — Landing Page
- [x] Public navbar (Home, Login, Register)
- [x] Hero section with tagline, description, Get Started + Explore Features buttons
- [x] Home feature card (3 features: Subjects, Tasks, Progress)
- [x] Features section (3 feature cards with icons)
- [x] CTA section (Create Account button)
- [x] Footer (© 2026 Prepexus)

### login.php — Login Page
- [x] Public navbar
- [x] Login box with heading (WELCOME BACK, Login to Prepexus)
- [x] Email + password form (POST)
- [x] Login button
- [x] Error message display ("Invalid email or password.")
- [x] Link to register.php ("Don't have an account? Create an account")
- [x] Footer

### register.php — Registration Page
- [x] Public navbar
- [x] Register box with heading (JOIN PREPEXUS, Create your account)
- [x] Form: Full Name, Email, Password, Course (text), Semester (dropdown 1-6)
- [x] Create Account button
- [x] Success/error message display
- [x] Link to login.php ("Already have an account?")
- [x] Footer

### dashboard.php — Dashboard
- [x] Dashboard navbar (logo + user avatar + name)
- [x] Collapsible sidebar (Dashboard active)
- [x] Welcome section ("Good morning, [Name]")
- [x] 4 stat cards (Subjects count, Tasks count, Materials count, Progress %)
- [x] Overall progress card (progress bar, "X of Y tasks completed")
- [x] Task status breakdown (Completed, In Progress, Pending counts)
- [x] Quick access cards (4 cards: Subjects, Study Planner, Materials, Progress)
- [x] Footer

### subjects.php — Subject Management
- [x] Dashboard navbar + sidebar (Subjects active)
- [x] Back button → dashboard.php
- [x] Page header (STUDY MANAGEMENT, My Subjects)
- [x] Success/error messages (added, updated, deleted)
- [x] Add/Edit form (toggle show/hide): Subject Name, Subject Code, Credit, Description
- [x] Subject table (No, Subject, Code, Credit, Description, Action)
- [x] Edit/Delete actions per row
- [x] Delete confirmation dialog
- [x] Empty state ("No subjects yet" + "Add Your First Subject" button)

### studyplanner.php — Study Planner
- [x] Dashboard navbar + sidebar (Study Planner active)
- [x] Back button → dashboard.php
- [x] Page header (STUDY MANAGEMENT, Study Planner)
- [x] Success messages (added, updated, deleted, status changed)
- [x] Add/Edit task form: Title, Subject (dropdown), Study Date, Due Date,
      Priority (High/Medium/Low), Status (Pending/In Progress/Completed), Description
- [x] Task table (No, Task, Subject, Study Date, Due Date, Priority, Status, Action)
- [x] Priority badges (colored by priority)
- [x] Status badges (checkmark for completed, colored by status)
- [x] Complete/Delete/Edit actions per row
- [x] Delete confirmation dialog
- [x] Empty state ("No study tasks yet" + "Add Your First Task" button)

### materials.php — Study Materials
- [x] Dashboard navbar + sidebar (Materials active)
- [x] Back button → dashboard.php
- [x] Page header (STUDY RESOURCES, Study Materials)
- [x] Success messages (added, updated, deleted)
- [x] Add/Edit form: Material Name, Subject (dropdown), Material Type
      (PDF/Video/Website/Notes), Material Link (URL), Description
- [x] Materials table (No, Material, Subject, Type, Description, Action)
- [x] Open/Edit/Delete actions per row (Open opens link in new tab)
- [x] Delete confirmation dialog

### progress.php — Progress Tracker
- [x] Dashboard navbar + sidebar (Progress active)
- [x] Back button → dashboard.php
- [x] Page header (PERFORMANCE TRACKER, Progress Tracker)
- [x] Overall progress card (progress bar, "Completed X/Y Tasks")
- [x] 4 stat cards (Total, Completed, In Progress, Pending)
- [x] Subject-wise progress list (subject name + progress bar + percentage)
- [x] "No subjects found" fallback

### profile.php — Profile Management
- [x] Dashboard navbar + sidebar (Profile active)
- [x] Back button → dashboard.php
- [x] Page header (ACCOUNT SETTINGS, My Profile)
- [x] Success message (profile updated)
- [x] Profile card: large avatar (initial) + name + email display
- [x] Edit form: Full Name, Email, Course, Semester (dropdown 1st-6th)
- [x] Save Changes button

## 4. Interactions

| Interaction | Implementation |
|------------|----------------|
| Sidebar toggle | `toggleSidebar()` — toggles `.open` on sidebar, `.show` on overlay |
| Form show/hide | `showForm()`/`hideForm()` (subjects), `showTaskForm()`/`hideTaskForm()` (planner), `showMaterialForm()`/`hideMaterialForm()` (materials) |
| Delete confirmation | `onclick="return confirm('...')"` |
| Complete task | `?complete=taskId` GET parameter (CSRF risk — see security model) |

## 5. Responsive Behavior

- All pages include `<meta name="viewport" content="width=device-width, initial-scale=1.0">`.
- Dashboard layout switches to mobile-optimized when sidebar is closed.
- Form rows use 2-column grid on desktop (e.g., Name + Course side by side on
  register, Priority + Status on planner).
- No media queries found in inline styles; CSS uses flexible units.

## 6. UI Confirmation Gate

The UI is already implemented and functional. Per §F, no "UI is final" or
"start backend" trigger is needed since the code already exists. This spec
documents the as-built UI for reference and regression testing.

## 7. Acceptance Criteria

- All pages render without errors in a modern browser.
- Sidebar toggles open/close on all dashboard pages.
- Forms validate required fields (HTML5).
- Tables display data correctly with proper escaping.
- Progress bars fill to the correct percentage.
- Empty states display when no data exists.
- Delete actions require confirmation.
- All pages link correctly to each other.
