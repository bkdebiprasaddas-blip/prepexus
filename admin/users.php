<?php

require_once __DIR__ . "/../includes/security.php";

include "../config/database.php";
include "header.php";

$message = "";

// CSRF token
$csrf_token = csrf_token();

// DELETE USER
if (isset($_POST['delete_user'])) {
    if (!verify_csrf()) { die('Invalid request.'); }

    $user_id = (int) $_POST['user_id'];

    if ($user_id <= 0) {

        $message = "Invalid user selected.";

    } elseif ($user_id === (int) $_SESSION['user_id']) {

        $message = "You cannot delete your own admin account while logged in.";

    } else {

        // Child rows first, because the foreign keys would otherwise
        // refuse the parent delete. Wrapped in a transaction so a
        // partial failure cannot leave an account with no data.
        // Every value is bound - no SQL is built by concatenation.
        mysqli_begin_transaction($conn);

        $ok = true;

        $child_deletes = array(
            "DELETE FROM tasks WHERE user_id = ?",
            "DELETE FROM materials WHERE user_id = ?",
            "DELETE FROM subjects WHERE user_id = ?",
        );

        foreach ($child_deletes as $sql) {

            $s = mysqli_prepare($conn, $sql);

            if (!$s) {
                $ok = false;
                break;
            }

            mysqli_stmt_bind_param($s, "i", $user_id);
            $ok = mysqli_stmt_execute($s);
            mysqli_stmt_close($s);

            if (!$ok) {
                break;
            }
        }

        if ($ok) {

            $s = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");

            if ($s) {
                mysqli_stmt_bind_param($s, "i", $user_id);

                // mysqli_stmt_execute() reports true for a statement that
                // matched nothing, so confirm a row really went.
                $ok = mysqli_stmt_execute($s)
                    && mysqli_stmt_affected_rows($s) > 0;

                mysqli_stmt_close($s);
            } else {
                $ok = false;
            }
        }

        if ($ok) {
            mysqli_commit($conn);
            $message = "User deleted successfully.";
        } else {
            mysqli_rollback($conn);
            $message = "Failed to delete user.";
        }
    }
}

// TOGGLE ROLE
if (isset($_POST['toggle_role'])) {
    if (!verify_csrf()) { die('Invalid request.'); }

    $user_id = (int) $_POST['user_id'];

    // Allow-listed: only these two values can ever reach the column.
    $new_role = ($_POST['new_role'] ?? '') === 'admin' ? 'admin' : 'student';

    if ($user_id <= 0) {

        $message = "Invalid user selected.";

    } elseif ($user_id === (int) $_SESSION['user_id']) {

        $message = "You cannot change your own admin role.";

    } else {

        $stmt = mysqli_prepare($conn, "UPDATE users SET role = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_role, $user_id);

        // True does not mean a row changed, so check affected rows.
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
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
                            <td><?php echo (int) $u['id']; ?></td>
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
                                        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                                        <input type="hidden" name="user_id" value="<?php echo (int) $u['id']; ?>">
                                        <input type="hidden" name="new_role" value="<?php echo $u['role'] === 'admin' ? 'student' : 'admin'; ?>">
                                        <button type="submit" name="toggle_role" class="btn-action btn-role">
                                            Make <?php echo $u['role'] === 'admin' ? 'Student' : 'Admin'; ?>
                                        </button>
                                    </form>

                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this user and all their associated data?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                                        <input type="hidden" name="user_id" value="<?php echo (int) $u['id']; ?>">
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
