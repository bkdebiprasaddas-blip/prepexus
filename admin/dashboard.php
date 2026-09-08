<?php

session_start();

include "../config/database.php";
include "header.php";

// System Metrics
$total_students = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'student'");
if ($row = mysqli_fetch_assoc($res)) { $total_students = $row['total']; }

$total_admins = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
if ($row = mysqli_fetch_assoc($res)) { $total_admins = $row['total']; }

$total_subjects = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM subjects");
if ($row = mysqli_fetch_assoc($res)) { $total_subjects = $row['total']; }

$total_tasks = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tasks");
if ($row = mysqli_fetch_assoc($res)) { $total_tasks = $row['total']; }

$total_materials = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM materials");
if ($row = mysqli_fetch_assoc($res)) { $total_materials = $row['total']; }

// Recent Registered Users
$recent_users = mysqli_query($conn, "SELECT id, name, email, course, semester, role FROM users ORDER BY id DESC LIMIT 5");

?>

<!-- MAIN DASHBOARD CONTENT -->
<main class="dashboard-content">
    <div class="admin-hero-card">
        <p class="dashboard-small-title" style="color: #60a5fa !important;">CONTROL CENTER</p>
        <h1>Welcome to Prepexus Admin Panel</h1>
        <p>Monitor platform statistics, manage registered users, and oversee educational resources.</p>
    </div>

    <!-- STATS GRID -->
    <div class="admin-grid-4">
        <div class="admin-stat-card">
            <div class="admin-stat-icon icon-users">👥</div>
            <div class="admin-stat-info">
                <p>Students</p>
                <h2><?php echo $total_students; ?></h2>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon icon-subjects">📚</div>
            <div class="admin-stat-info">
                <p>Total Subjects</p>
                <h2><?php echo $total_subjects; ?></h2>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon icon-tasks">✓</div>
            <div class="admin-stat-info">
                <p>Total Tasks</p>
                <h2><?php echo $total_tasks; ?></h2>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon icon-materials">📖</div>
            <div class="admin-stat-info">
                <p>Materials</p>
                <h2><?php echo $total_materials; ?></h2>
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS & RECENT USERS -->
    <div class="admin-table-card">
        <div class="admin-table-header">
            <h2>Recent User Registrations</h2>
            <div style="display: flex; gap: 10px;">
                <a href="users.php" class="btn-action btn-role">Manage All Users (<?php echo ($total_students + $total_admins); ?>) →</a>
            </div>
        </div>

        <div class="subject-table-container">
            <table class="subject-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Semester</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = mysqli_fetch_assoc($recent_users)) { ?>
                        <tr>
                            <td><?php echo $u['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['course']); ?></td>
                            <td><?php echo htmlspecialchars($u['semester']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $u['role'] === 'admin' ? 'status-completed' : 'status-in-progress'; ?>">
                                    <?php echo strtoupper($u['role']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include "footer.php"; ?>
