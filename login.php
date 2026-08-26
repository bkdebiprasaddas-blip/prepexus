<?php

session_start();

include "config/database.php";

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
// Already logged in? Go to dashboard.
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$message = "";

// Show confirmation after successful registration
if (isset($_GET['registered'])) {
    $message = "Registration successful! Please log in.";
}

if (isset($_POST['login'])) {

    if (!verify_csrf()) { die('Invalid request.'); }

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepared statement -- fetch user by email only (password verified in PHP)
    $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {

            $user = mysqli_fetch_assoc($result);

            // Verify hashed password
            if (password_verify($password, $user['password'])) {

                // Regenerate session ID to prevent fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];

                header("Location: dashboard.php");
                exit();

            } else {
                $message = "Invalid email or password.";
            }

        } else {
            $message = "Invalid email or password.";
        }

        mysqli_stmt_close($stmt);
    } else {
        $message = "An error occurred. Please try again.";
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
                     value="<?php echo $csrf_token; ?>">

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
