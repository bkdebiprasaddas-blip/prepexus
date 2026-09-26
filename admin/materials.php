<?php

require_once __DIR__ . "/../includes/security.php";

include "../config/database.php";
include "header.php";

$message = "";

// CSRF token
$csrf_token = csrf_token();

// DELETE MATERIAL AS ADMIN
if (isset($_POST['delete_material'])) {
    if (!verify_csrf()) { die('Invalid request.'); }

    $material_id = (int) $_POST['material_id'];

    if ($material_id <= 0) {
        $message = "Invalid material selected.";
    } else {

        $stmt = mysqli_prepare($conn, "DELETE FROM materials WHERE id = ?");

        // True does not mean a row was matched, so confirm.
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $material_id);

            if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
                $message = "Study material deleted successfully.";
            } else {
                $message = "Failed to delete study material.";
            }

            mysqli_stmt_close($stmt);
        } else {
            $message = "Failed to delete study material.";
        }
    }
}

// FETCH ALL MATERIALS WITH USER & SUBJECT NAME
$query = "SELECT m.*, u.name as student_name, s.subject_name 
          FROM materials m 
          JOIN users u ON m.user_id = u.id 
          JOIN subjects s ON m.subject_id = s.id 
          ORDER BY m.id DESC";
$materials_result = mysqli_query($conn, $query);

?>

<main class="dashboard-content">
    <div class="welcome-section">
        <p class="dashboard-small-title">CONTENT MANAGEMENT</p>
        <h1>All Uploaded Materials</h1>
        <p>Overview of study resources and link materials attached by students across the platform.</p>
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
                        <th>Student</th>
                        <th>Subject</th>
                        <th>Material Name</th>
                        <th>Type</th>
                        <th>Link / URL</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($m = mysqli_fetch_assoc($materials_result)) { ?>
                        <tr>
                            <td><?php echo (int) $m['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($m['student_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($m['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($m['material_name']); ?></td>
                            <td>
                                <span class="status-badge status-in-progress">
                                    <?php echo htmlspecialchars($m['material_type']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo e(safe_url($m['material_link'])); ?>" target="_blank" rel="noopener noreferrer" class="btn-action btn-role">
                                    Open Link ↗
                                </a>
                            </td>
                            <td style="text-align: center;">
                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this study material?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                                    <input type="hidden" name="material_id" value="<?php echo (int) $m['id']; ?>">
                                    <button type="submit" name="delete_material" class="btn-action btn-danger">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include "footer.php"; ?>
