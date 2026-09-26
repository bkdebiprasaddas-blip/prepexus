<?php

require_once __DIR__ . "/includes/security.php";

include "config/database.php";

// CSRF token
$csrf_token = csrf_token();

// Already logged in? Redirect based on role.
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$message = "";

// Show confirmation after successful registration
if (isset($_GET['registered'])) {
    $message = "Registration successful! Please log in.";
}

if (isset($_POST['login'])) {

    if (!verify_csrf()) { die('Invalid request.'); }

    $email = normalize_email($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip = client_ip();

    // Brute-force throttle. Without it this endpoint accepts an
    // unlimited number of guesses against a known address.
    if (login_is_locked($conn, $email, $ip)) {
        $message = "Too many failed attempts. Please try again in a few minutes.";
    } else {

        // Prepared statement -- fetch user by email only (password verified in PHP)
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);

        $authenticated = false;
        $user = null;

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) == 1) {

                $candidate = mysqli_fetch_assoc($result);

                // Verify hashed password
                if ($candidate && password_verify($password, $candidate['password'])) {

                    $user = $candidate;
                    $authenticated = true;
                }
            }

            mysqli_stmt_close($stmt);

        } else {
            $message = "An error occurred. Please try again.";
        }

        if ($message === "" && !$authenticated) {

            // Identical wording for "no such account" and "wrong password",
            // so the form cannot be used to enumerate registered addresses.
            $message = "Invalid email or password.";
        }

        record_login_attempt($conn, $email, $ip, $authenticated);

        if ($authenticated) {

            clear_login_attempts($conn, $email, $ip);

            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);

            // A new token after the privilege change, so a token that
            // was readable before login cannot be replayed afterwards.
            rotate_csrf_token();

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <title>Login - Prepexus</title>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" type="text/css" href="css/style.css">

</head>

<body>

<?php
include "includes/navbar.php";
?>


<div class="login-page">

    <div class="login-box">

        <div class="login-heading">

            <p class="login-small-title">
                WELCOME BACK
            </p>

            <h1>Login to Prepexus</h1>

            <p>
                Continue your study journey.
            </p>

        </div>


        <?php if ($message != "") { ?>

            <div class="login-message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php } ?>


        <form method="POST">
                <input type="hidden" name="csrf_token"
                     value="<?php echo e($csrf_token); ?>">

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
                    placeholder="Enter your password"
                    required
                >

            </div>


            <button
                type="submit"
                name="login"
                class="login-button"
            >
                Login
            </button>

        </form>


        <p class="register-text">

            Don't have an account?

            <a href="register.php">
                Create an account
            </a>

        </p>

    </div>

</div>


<footer>

    <p>© 2026 Prepexus. Student Study Tracker.</p>

</footer>


</body>

</html>
