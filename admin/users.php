<?php

session_start();

include "../config/database.php";
include "header.php";

$message = "";

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

function verify_csrf() {
    return isset($_POST['csrf_token'])
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// DELETE USER
if (isset($_POST['delete_user'])) {
    if (!verify_csrf()) { die('Invalid request.'); }
    $user_id = (int)$_POST['user_id'];
    
    if ($user_id === (int)$_SESSION['user_id']) {
        $message = "You cannot delete your own admin account while logged in.";
    } else {
        mysqli_query($conn, "DELETE FROM tasks WHERE user_id = $user_id");
        mysqli_query($conn, "DELETE FROM materials WHERE user_id = $user_id");
        mysqli_query($conn, "DELETE FROM subjects WHERE user_id = $user_id");
        $del = mysqli_query($conn, "DELETE FROM users WHERE id = $user_id");
        
        if ($del) {
            $message = "User deleted successfully.";
        } else {
            $message = "Failed to delete user.";
        }
    }
}

// TOGGLE ROLE
if (isset($_POST['toggle_role'])) {
    if (!verify_csrf()) { die('Invalid request.'); }
    $user_id = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'] === 'admin' ? 'admin' : 'student';
    
    if ($user_id === (int)$_SESSION['user_id']) {
        $message = "You cannot change your own admin role.";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET role = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_role, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "User role updated successfully.";
        } else {
            $message = "Failed to update user role.";
        }
        mysqli_stmt_close($stmt);
    }
}

// FETCH ALL USERS
$users_result = mysqli_query($conn, "SELECT id, name, email, course, semester, role FROM users ORDER BY id ASC");

?>

<main class="dashboard-content">
    <div class="welcome-section">
        <p class="dashboard-small-title">ADMINISTRATION</p>
        <h1>User Directory & Roles</h1>
        <p>Manage system users, grant administrative privileges, or remove accounts.</p>
    </div>

    <?php if ($message != "") { ?>
        <div class="login-message" style="margin-bottom: 20px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php } ?>

    <div class="admin-table-card">
        <div class="subject-table-container">
            <table class="subject-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Semester</th>
                        <th>Role</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = mysqli_fetch_assoc($users_result)) { ?>
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
                            <td style="text-align: center;">
                                <?php if ($u['id'] != $_SESSION['user_id']) { ?>
                                    <form method="POST" style="display:inline-block; margin-right: 6px;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="new_role" value="<?php echo $u['role'] === 'admin' ? 'student' : 'admin'; ?>">
                                        <button type="submit" name="toggle_role" class="btn-action btn-role">
                                            Make <?php echo $u['role'] === 'admin' ? 'Student' : 'Admin'; ?>
                                        </button>
                                    </form>

                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this user and all their associated data?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn-action btn-danger">
                                            Delete
                                        </button>
                                    </form>
                                <?php } else { ?>
                                    <span style="font-size: 12px; color: #95a5a6; font-style: italic;">Active Session</span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include "footer.php"; ?>
