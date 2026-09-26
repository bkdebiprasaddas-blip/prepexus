<?php
// Single source of truth for admin access control. Passes $conn so the
// role is re-read from the users row instead of trusting the session copy.
require_admin($conn ?? null);

$admin_name = $_SESSION['user_name'];

// Allow-listed: $_SERVER values are request-controlled, so the value is
// matched against a fixed set before it is ever echoed.
$admin_pages = array('dashboard.php', 'users.php', 'subjects.php', 'materials.php');
$current_page = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

if (!in_array($current_page, $admin_pages, true)) {
    $current_page = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../css/style.css">
    <style>
        /* Admin Specific UI Enhancements */
        .admin-badge {
            background-color: #e74c3c;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 12px;
            margin-left: 8px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .admin-hero-card {
            background: linear-gradient(135deg, #172033 0%, #2c3e50 100%);
            color: white;
            border-radius: 12px;
            padding: 28px 32px;
            margin-bottom: 30px;
            box-shadow: 0 8px 24px rgba(23, 32, 51, 0.12);
        }
        .admin-hero-card h1 {
            color: white !important;
            margin: 5px 0 10px 0 !important;
            font-size: 28px !important;
        }
        .admin-hero-card p {
            color: #bdc3c7 !important;
            font-size: 15px;
        }
        .admin-grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .admin-stat-card {
            background: #ffffff;
            border: 1px solid #e5eaf2;
            border-radius: 12px;
            padding: 22px 24px;
            display: flex;
            align-items: center;
            gap: 18px;
            box-shadow: 0 4px 12px rgba(20, 40, 70, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .admin-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(20, 40, 70, 0.08);
        }
        .admin-stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }
        .icon-users { background: #eef5ff; color: #2f80ed; }
        .icon-subjects { background: #f0fdf4; color: #27ae60; }
        .icon-tasks { background: #fefce8; color: #f2994a; }
        .icon-materials { background: #faf5ff; color: #9b51e0; }
        
        .admin-stat-info p {
            margin: 0 0 4px 0;
            font-size: 13px;
            color: #68758b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .admin-stat-info h2 {
            margin: 0;
            font-size: 26px;
            color: #172033;
            font-weight: 700;
        }
        .admin-table-card {
            background: #ffffff;
            border: 1px solid #e5eaf2;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 14px rgba(20, 40, 70, 0.04);
        }
        .admin-table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .admin-table-header h2 {
            margin: 0;
            font-size: 20px;
            color: #172033;
        }
        .btn-action {
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-role {
            background-color: #eef5ff;
            color: #2f80ed;
            border: 1px solid #cce0ff;
        }
        .btn-role:hover {
            background-color: #2f80ed;
            color: #ffffff;
        }
        .btn-danger {
            background-color: #fdf2f2;
            color: #e74c3c;
            border: 1px solid #fecdcd;
        }
        .btn-danger:hover {
            background-color: #e74c3c;
            color: #ffffff;
        }
    </style>
</head>
<body>

<!-- DASHBOARD HEADER -->
<nav class="dashboard-navbar">
    <div class="dashboard-left">
        <button class="menu-button" onclick="toggleSidebar()" type="button" aria-label="Toggle Sidebar Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <a href="dashboard.php" class="dashboard-logo">
            <img src="../images/prepxus.png" alt="Prepexus Logo">
        </a>
    </div>
    <div class="dashboard-user">
        <div class="user-avatar" style="background-color: #e74c3c; color: white;">
            <?php echo initial($admin_name); ?>
        </div>
        <span>
            <?php echo htmlspecialchars($admin_name); ?>
            <span class="admin-badge">Admin</span>
        </span>
    </div>
</nav>

<!-- SIDEBAR -->
<aside id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <span class="sidebar-title">ADMIN CONTROL PANEL</span>
        <button onclick="toggleSidebar()" class="close-button" type="button">×</button>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <span class="sidebar-icon">🏠</span> Dashboard
        </a>
        <a href="users.php" class="<?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
            <span class="sidebar-icon">👥</span> Manage Users
        </a>
        <a href="subjects.php" class="<?php echo $current_page === 'subjects.php' ? 'active' : ''; ?>">
            <span class="sidebar-icon">📚</span> All Subjects
        </a>
        <a href="materials.php" class="<?php echo $current_page === 'materials.php' ? 'active' : ''; ?>">
            <span class="sidebar-icon">📖</span> All Materials
        </a>
        <a href="../logout.php?token=<?php echo e(csrf_token()); ?>" class="sidebar-logout">
            <span class="sidebar-icon">🚪</span> Logout
        </a>
    </div>
</aside>

<!-- SIDEBAR OVERLAY -->
<div id="overlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>
