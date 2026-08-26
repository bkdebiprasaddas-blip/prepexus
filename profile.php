<?php

session_start();

include "config/database.php";


// ==================================================
// CHECK LOGIN
// ==================================================

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");

    exit();

}

$user_id = $_SESSION['user_id'];

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


$message = "";


// ==================================================
// UPDATE PROFILE
// ==================================================

if (isset($_POST['update_profile'])) {

    if (!verify_csrf()) { die('Invalid request.'); }

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $course = trim($_POST['course']);
    $semester = $_POST['semester'];

    // Validate required fields
    if ($name == "" || $email == "") {

        $message = "Name and email are required.";

    } else {

        // Check the new email is not used by ANOTHER account
        $check_stmt = mysqli_prepare($conn,
            "SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1"
        );

        $email_taken = false;

        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $email_taken = true;
            }
            mysqli_stmt_close($check_stmt);
        }

        if ($email_taken) {

            $message = "This email is already in use by another account.";

        } else {

            // Prepared statement -- prevents SQL injection
            $stmt = mysqli_prepare($conn,
                "UPDATE users SET
                    name = ?,
                    email = ?,
                    course = ?,
                    semester = ?
                 WHERE id = ?"
            );

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssssi",
                    $name, $email, $course, $semester, $user_id
                );

                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['user_name'] = $name;
                    header("Location: profile.php?success=1");
                    exit();
                } else {
                    $message = "Update failed. Please try again.";
                }
                mysqli_stmt_close($stmt);
            } else {
                $message = "Update failed. Please try again.";
            }
        }
    }
}


// ==================================================
// GET USER INFORMATION
// ==================================================

$user_stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? LIMIT 1");

if ($user_stmt) {
    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
    mysqli_stmt_execute($user_stmt);
    $result = mysqli_stmt_get_result($user_stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($user_stmt);
}


?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Profile - Prepexus</title>


    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>


<body>


<!-- ==================================================
     NAVBAR
     ================================================== -->

<nav class="dashboard-navbar">


    <div class="dashboard-left">


        <button
            class="menu-button"
            onclick="toggleSidebar()"
            type="button"
        >

            <span></span>
            <span></span>
            <span></span>

        </button>


        <a
            href="dashboard.php"
            class="dashboard-logo"
        >

            <img
                src="images/prepxus.png"
                alt="Prepexus Logo"
            >

        </a>


    </div>



    <div class="dashboard-user">


        <div class="user-avatar">

            <?php

            echo strtoupper(
                substr($user['name'], 0, 1)
            );

            ?>

        </div>


        <span>

            <?php

            echo htmlspecialchars(
                $user['name']
            );

            ?>

        </span>


    </div>


</nav>



<!-- ==================================================
     SIDEBAR
     ================================================== -->

<aside
    id="sidebar"
    class="sidebar"
>


    <div class="sidebar-header">


        <span class="sidebar-title">

            MENU

        </span>


        <button
            onclick="toggleSidebar()"
            class="close-button"
            type="button"
        >

            ×

        </button>


    </div>



    <div class="sidebar-menu">


        <a href="dashboard.php">

            <span class="sidebar-icon">

                🏠

            </span>

            Dashboard

        </a>



        <a href="subjects.php">

            <span class="sidebar-icon">

                📚

            </span>

            Subjects

        </a>



        <a href="studyplanner.php">

            <span class="sidebar-icon">

                ✓

            </span>

            Study Planner

        </a>



        <a href="materials.php">

            <span class="sidebar-icon">

                📖

            </span>

            Study Materials

        </a>



        <a href="progress.php">

            <span class="sidebar-icon">

                📊

            </span>

            Progress Tracker

        </a>



        <!-- CURRENT PAGE -->

        <a
            href="profile.php"
            class="active"
        >

            <span class="sidebar-icon">

                👤

            </span>

            Profile

        </a>



        <a
            href="logout.php"
            class="sidebar-logout"
        >

            <span class="sidebar-icon">

                🚪

            </span>

            Logout

        </a>


    </div>


</aside>



<!-- ==================================================
     OVERLAY
     ================================================== -->

<div
    id="overlay"
    class="sidebar-overlay"
    onclick="toggleSidebar()"
>
</div>



<!-- ==================================================
     PROFILE PAGE
     ================================================== -->

<main class="profile-page">


    <!-- BACK BUTTON -->

    <a
        href="dashboard.php"
        class="back-button"
    >

        ← Back to Dashboard

    </a>



    <!-- HEADER -->

    <div class="profile-header">


        <p class="dashboard-small-title">

            ACCOUNT SETTINGS

        </p>


        <h1>

            My Profile

        </h1>


        <p>

            View and update your personal information.

        </p>


    </div>



    <!-- SUCCESS MESSAGE -->

    <?php if (isset($_GET['success'])) { ?>

        <div class="profile-success">

            Profile updated successfully.

        </div>

    <?php } ?>


    <!-- ERROR MESSAGE -->

    <?php if (isset($message) && $message != "") { ?>

        <div class="profile-error">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php } ?>



    <!-- PROFILE CARD -->

    <div class="profile-card">


        <!-- PROFILE TOP -->

        <div class="profile-card-top">


            <div class="profile-avatar-large">

                <?php

                echo strtoupper(
                    substr($user['name'], 0, 1)
                );

                ?>

            </div>


            <div>

                <h2>

                    <?php

                    echo htmlspecialchars(
                        $user['name']
                    );

                    ?>

                </h2>


                <p>

                    <?php

                    echo htmlspecialchars(
                        $user['email']
                    );

                    ?>

                </p>

            </div>


        </div>



        <!-- FORM -->

        <form method="POST">
                <input type="hidden" name="csrf_token"
                     value="<?php echo $csrf_token; ?>">


            <!-- NAME -->

            <div class="profile-input-group">


                <label>

                    Full Name

                </label>


                <input
                    type="text"
                    name="name"
                    value="<?php

                    echo htmlspecialchars(
                        $user['name']
                    );

                    ?>"
                    required
                >


            </div>



            <!-- EMAIL -->

            <div class="profile-input-group">


                <label>

                    Email Address

                </label>


                <input
                    type="email"
                    name="email"
                    value="<?php

                    echo htmlspecialchars(
                        $user['email']
                    );

                    ?>"
                    required
                >


            </div>



            <!-- COURSE -->

            <div class="profile-input-group">


                <label>

                    Course

                </label>


                <input
                    type="text"
                    name="course"
                    placeholder="Example: BCA"
                    value="<?php

                    if (isset($user['course'])) {

                        echo htmlspecialchars(
                            $user['course']
                        );

                    }

                    ?>"
                >


            </div>



            <!-- SEMESTER -->

            <div class="profile-input-group">


                <label>

                    Semester

                </label>


                <select
                    name="semester"
                >


                    <option value="">

                        Select Semester

                    </option>


                    <?php

                    $semesters = [
                        "1st Semester",
                        "2nd Semester",
                        "3rd Semester",
                        "4th Semester",
                        "5th Semester",
                        "6th Semester"
                    ];


                    foreach (
                        $semesters
                        as $semester
                    ) {

                    ?>


                        <option
                            value="<?php
                            echo $semester;
                            ?>"

                            <?php

                            if (
                                isset(
                                    $user['semester']
                                )
                                &&
                                $user['semester']
                                == $semester
                            ) {

                                echo "selected";

                            }

                            ?>
                        >

                            <?php

                            echo $semester;

                            ?>

                        </option>


                    <?php

                    }

                    ?>


                </select>


            </div>



            <!-- SAVE BUTTON -->

            <button
                type="submit"
                name="update_profile"
                class="profile-save-button"
            >

                Save Changes

            </button>


        </form>


    </div>


</main>



<script>

function toggleSidebar() {

    var sidebar =
        document.getElementById("sidebar");

    var overlay =
        document.getElementById("overlay");


    sidebar.classList.toggle("open");

    overlay.classList.toggle("show");

}

</script>


</body>

</html>
