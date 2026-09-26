<?php

require_once __DIR__ . "/includes/security.php";

include "config/database.php";

// Already logged in? Go to dashboard.
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// CSRF token
$csrf_token = csrf_token();


$message = "";// Show validation errors
if (isset($_GET['error']) && $_GET['error'] === 'pw') {
    $message = "Password must be at least 8 characters and both passwords must match.";
}

if (isset($_POST['register'])) {

    if (!verify_csrf()) { die('Invalid request.'); }

    // Server-side validation. The form constraints are a convenience
    // for the visitor, never a guarantee about what arrives here.
    $name = trim($_POST['name'] ?? '');
    $email = normalize_email($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $course = trim($_POST['course'] ?? '');
    $semester = $_POST['semester'] ?? '';

    // Password policy (B-022 / B-024)
    $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (strlen($password) < 8 || $password !== $confirm) {
        header("Location: register.php?error=pw");
        exit();
    }

    if ($name === '' || strlen($name) > 100) {
        $message = "Please enter your full name (100 characters max).";
    } elseif (!is_valid_email($email)) {
        $message = "Please enter a valid email address.";
    } elseif ($course === '' || strlen($course) > 50) {
        $message = "Please enter your course (50 characters max).";
    } elseif (!is_valid_semester($semester)) {
        $message = "Please select a valid semester.";
    } else {

        // Check for duplicate email using prepared statement.
        // The UNIQUE index on users.email is the real guarantee: this
        // SELECT cannot rule out a concurrent signup, so the insert
        // error below is treated as authoritative.
        $check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "s", $email);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);

            $duplicate = $check_result && mysqli_num_rows($check_result) > 0;

            mysqli_stmt_close($check_stmt);

            if ($duplicate) {
                $message = "Email already registered.";
            } else {
                // Hash password before storing
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Insert using prepared statement
                $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, course, semester) VALUES (?, ?, ?, ?, ?)");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $password_hash, $course, $semester);

                    if (mysqli_stmt_execute($stmt)) {

                        // Rotate now that the account exists, so the
                        // pre-registration token cannot be reused.
                        rotate_csrf_token();

                        header("Location: login.php?registered=1");
                        exit();

                    } else {

                        // 1062 = duplicate key: lost the registration race.
                        $message = (mysqli_errno($conn) === 1062)
                            ? "Email already registered."
                            : "Registration failed. Please try again.";
                    }

                    mysqli_stmt_close($stmt);
                } else {
                    $message = "Registration failed. Please try again.";
                }
            }
        } else {
            $message = "Registration failed. Please try again.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <title>Register - Prepexus</title>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" type="text/css" href="css/style.css">

</head>

<body>

<?php
include "includes/navbar.php";
?>


<div class="register-page">

    <div class="register-box">

        <div class="register-heading">

            <p class="register-small-title">
                JOIN PREPEXUS
            </p>

            <h1>Create your account</h1>

            <p>
                Start organizing your study journey today.
            </p>

        </div>


        <?php if ($message != "") { ?>

            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php } ?>


        <form method="POST">
                <input type="hidden" name="csrf_token"
                     value="<?php echo e($csrf_token); ?>">


            <div class="form-group">

                <label>Full Name</label>

                <input
                    type="text"
                    name="name"
                    placeholder="Enter your name"
                    required
                >

            </div>


            <div class="form-group">

                <label>Email Address</label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >

            </div>


            <div class="form-group">

                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    placeholder="Create a password"
                    required
                >

            </div>


            <div class="form-row">


                <div class="form-group">

                    <label>Course</label>

                    <input
                        type="text"
                        name="course"
                        placeholder="Example: BCA"
                        required
                    >

                </div>

            <div class="form-group">

                <label>Confirm Password</label>

                <input
                    type="password"
                    name="confirm_password"
                    placeholder="Re-enter your password"
                    required
                >

            </div>


                <div class="form-group">

                    <label>Semester</label>

                    <select name="semester" required>

                        <option value="">Select</option>

                        <option value="1st Semester">1st Semester</option>

                        <option value="2nd Semester">2nd Semester</option>

                        <option value="3rd Semester">3rd Semester</option>

                        <option value="4th Semester">4th Semester</option>

                        <option value="5th Semester">5th Semester</option>

                        <option value="6th Semester">6th Semester</option>

                    </select>

                </div>


            </div>


            <button
                type="submit"
                name="register"
                class="register-button"
            >
                Create Account
            </button>


        </form>


        <p class="login-text">

            Already have an account?

            <a href="login.php">Login here</a>

        </p>


    </div>

</div>


<footer>

    <p>© 2026 Prepexus. Student Study Tracker.</p>

</footer>


</body>

</html>
