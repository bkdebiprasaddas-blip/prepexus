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

// DELETE SUBJECT AS ADMIN
if (isset($_POST['delete_subject'])) {
    if (!verify_csrf()) { die('Invalid request.'); }
    $subject_id = (int)$_POST['subject_id'];

    mysqli_query($conn, "DELETE FROM tasks WHERE subject_id = $subject_id");
    mysqli_query($conn, "DELETE FROM materials WHERE subject_id = $subject_id");
    $del = mysqli_query($conn, "DELETE FROM subjects WHERE id = $subject_id");

    if ($del) {
        $message = "Subject and associated data deleted successfully.";
    } else {
        $message = "Failed to delete subject.";
    }
}

// FETCH ALL SUBJECTS WITH USER NAME
$query = "SELECT s.*, u.name as student_name, u.email as student_email 
          FROM subjects s 
          JOIN users u ON s.user_id = u.id 
          ORDER BY s.id DESC";
$subjects_result = mysqli_query($conn, $query);

?>

<main class="dashboard-content">
    <div class="welcome-section">
        <p class="dashboard-small-title">CONTENT MANAGEMENT</p>
        <h1>All Student Subjects</h1>
        <p>Review and moderate subjects registered by students across the platform.</p>
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
                        <th>Subject Name</th>
                        <th>Code</th>
                        <th>Credits</th>
                        <th>Description</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($s = mysqli_fetch_assoc($subjects_result)) { ?>
                        <tr>
                            <td><?php echo $s['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($s['student_name']); ?></strong><br>
                                <small style="color: #7f8c8d;"><?php echo htmlspecialchars($s['student_email']); ?></small>
                            </td>
                            <td><strong><?php echo htmlspecialchars($s['subject_name']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($s['subject_code']); ?></code></td>
                            <td><?php echo htmlspecialchars($s['credit']); ?></td>
                            <td><?php echo htmlspecialchars($s['description']); ?></td>
                            <td style="text-align: center;">
                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this subject and all its associated tasks and materials?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="subject_id" value="<?php echo $s['id']; ?>">
                                    <button type="submit" name="delete_subject" class="btn-action btn-danger">
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
