<?php

require_once __DIR__ . "/includes/security.php";

include "config/database.php";


// ==================================================
// CHECK LOGIN
// ==================================================

require_login();

$user_id = current_user_id();


// ==================================================
// USER INFORMATION
// ==================================================

$user_name = $_SESSION['user_name'];


// ==================================================
// COUNT SUBJECTS
// ==================================================

$stmt = mysqli_prepare($conn,
    "SELECT COUNT(*) AS total FROM subjects WHERE user_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$total_subjects = $row['total'];
mysqli_stmt_close($stmt);


// ==================================================
// COUNT TASKS
// ==================================================

$stmt = mysqli_prepare($conn,
    "SELECT COUNT(*) AS total FROM tasks WHERE user_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$total_tasks = $row['total'];
mysqli_stmt_close($stmt);


// ==================================================
// COUNT MATERIALS
// ==================================================

$stmt = mysqli_prepare($conn,
    "SELECT COUNT(*) AS total FROM materials WHERE user_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$total_materials = $row['total'];
mysqli_stmt_close($stmt);


// ==================================================
// COMPLETED TASKS
// ==================================================

$status_c = 'Completed';
$stmt = mysqli_prepare($conn,
    "SELECT COUNT(*) AS completed FROM tasks WHERE user_id = ? AND status = ?"
);
mysqli_stmt_bind_param($stmt, "is", $user_id, $status_c);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$completed_tasks = $row['completed'];
mysqli_stmt_close($stmt);


// ==================================================
// PENDING TASKS
// ==================================================

$status_p = 'Pending';
$stmt = mysqli_prepare($conn,
    "SELECT COUNT(*) AS pending FROM tasks WHERE user_id = ? AND status = ?"
);
mysqli_stmt_bind_param($stmt, "is", $user_id, $status_p);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$pending_tasks = $row['pending'];
mysqli_stmt_close($stmt);


// ==================================================
// IN PROGRESS TASKS
// ==================================================

$status_i = 'In Progress';
$stmt = mysqli_prepare($conn,
    "SELECT COUNT(*) AS progress FROM tasks WHERE user_id = ? AND status = ?"
);
mysqli_stmt_bind_param($stmt, "is", $user_id, $status_i);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$in_progress_tasks = $row['progress'];
mysqli_stmt_close($stmt);


// ==================================================
// OVERALL PROGRESS
// ==================================================

if ($total_tasks > 0) {

    $overall_progress =
        round(
            ($completed_tasks / $total_tasks) * 100
        );

} else {

    $overall_progress = 0;

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <title>Dashboard - Prepexus</title>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <link
        rel="stylesheet"
        type="text/css"
        href="css/style.css"
    >

</head>


<body>


<!-- ==================================================
     DASHBOARD HEADER
     ================================================== -->

<nav class="dashboard-navbar">


    <!-- LEFT SIDE -->

    <div class="dashboard-left">


        <!-- THREE LINE MENU -->

        <button
            class="menu-button"
            onclick="toggleSidebar()"
            type="button"
        >

            <span></span>

            <span></span>

            <span></span>

        </button>



        <!-- LOGO -->

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



    <!-- RIGHT SIDE USER -->

    <div class="dashboard-user">


        <div class="user-avatar">

            <?php

            echo initial(
                $user_name
            );

            ?>

        </div>


        <span>

            <?php

            echo htmlspecialchars($user_name);

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


    <!-- SIDEBAR HEADER -->

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



    <!-- SIDEBAR MENU -->

    <div class="sidebar-menu">


        <!-- DASHBOARD -->

        <a
            href="dashboard.php"
            class="active"
        >

            <span class="sidebar-icon">

                🏠

            </span>

            Dashboard

        </a>



        <!-- SUBJECTS -->

        <a href="subjects.php">

            <span class="sidebar-icon">

                📚

            </span>

            Subjects

        </a>



        <!-- STUDY PLANNER -->

        <a href="studyplanner.php">

            <span class="sidebar-icon">

                ✓

            </span>

            Study Planner

        </a>



        <!-- MATERIALS -->

        <a href="materials.php">

            <span class="sidebar-icon">

                📖

            </span>

            Study Materials

        </a>



        <!-- PROGRESS TRACKER -->

        <a href="progress.php">

            <span class="sidebar-icon">

                📊

            </span>

            Progress Tracker

        </a>



        <!-- PROFILE -->

        <a href="profile.php">

            <span class="sidebar-icon">

                👤

            </span>

            Profile

        </a>



        <!-- LOGOUT -->

        <a
            href="logout.php?token=<?php echo e(csrf_token()); ?>"
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
     DARK OVERLAY
     ================================================== -->

<div
    id="overlay"
    class="sidebar-overlay"
    onclick="toggleSidebar()"
>
</div>



<!-- ==================================================
     MAIN DASHBOARD
     ================================================== -->

<main class="dashboard-content">



    <!-- ==================================================
         WELCOME SECTION
         ================================================== -->

    <div class="welcome-section">


        <p class="dashboard-small-title">

            WELCOME BACK

        </p>


        <h1>

            Good morning,

            <?php

            echo htmlspecialchars($user_name);

            ?>

            👋

        </h1>


        <p>

            Here's an overview of your study journey.

        </p>


    </div>



    <!-- ==================================================
         STATISTICS CARDS
         ================================================== -->

    <div class="dashboard-cards">



        <!-- SUBJECTS -->

        <div class="dashboard-card">


            <div class="dashboard-icon">

                📚

            </div>


            <div>

                <p>

                    Subjects

                </p>


                <h2>

                    <?php

                    echo $total_subjects;

                    ?>

                </h2>

            </div>


        </div>



        <!-- TASKS -->

        <div class="dashboard-card">


            <div class="dashboard-icon">

                ✓

            </div>


            <div>

                <p>

                    Study Tasks

                </p>


                <h2>

                    <?php

                    echo $total_tasks;

                    ?>

                </h2>

            </div>


        </div>



        <!-- MATERIALS -->

        <div class="dashboard-card">


            <div class="dashboard-icon">

                📖

            </div>


            <div>

                <p>

                    Materials

                </p>


                <h2>

                    <?php

                    echo $total_materials;

                    ?>

                </h2>

            </div>


        </div>



        <!-- PROGRESS -->

        <div class="dashboard-card">


            <div class="dashboard-icon">

                📊

            </div>


            <div>

                <p>

                    Overall Progress

                </p>


                <h2>

                    <?php

                    echo $overall_progress;

                    ?>%

                </h2>

            </div>


        </div>


    </div>



    <!-- ==================================================
         PROGRESS SECTION
         ================================================== -->

    <div class="dashboard-progress-section">


        <div class="dashboard-progress-card">


            <div class="dashboard-progress-heading">


                <div>


                    <p class="dashboard-small-title">

                        YOUR PROGRESS

                    </p>


                    <h2>

                        Overall Study Progress

                    </h2>


                </div>


                <a
                    href="progress.php"
                    class="view-progress-button"
                >

                    View Details →

                </a>


            </div>



            <!-- PROGRESS NUMBER -->

            <div class="dashboard-progress-number">

                <?php

                echo $overall_progress;

                ?>%

            </div>



            <!-- PROGRESS BAR -->

            <div class="dashboard-progress-bar">


                <div
                    class="dashboard-progress-fill"
                    style="
                        width:
                        <?php
                        echo $overall_progress;
                        ?>%;
                    "
                >
                </div>


            </div>



            <!-- PROGRESS TEXT -->

            <p class="dashboard-progress-text">

                You have completed

                <strong>

                    <?php

                    echo $completed_tasks;

                    ?>

                </strong>

                out of

                <strong>

                    <?php

                    echo $total_tasks;

                    ?>

                </strong>

                study tasks.

            </p>


        </div>



        <!-- ==================================================
             TASK STATUS
             ================================================== -->

        <div class="task-status-card">


            <h2>

                Task Status

            </h2>


            <p>

                Current status of your study tasks.

            </p>



            <!-- COMPLETED -->

            <div class="status-row">


                <span>

                    Completed

                </span>


                <strong>

                    <?php

                    echo $completed_tasks;

                    ?>

                </strong>


            </div>



            <!-- IN PROGRESS -->

            <div class="status-row">


                <span>

                    In Progress

                </span>


                <strong>

                    <?php

                    echo $in_progress_tasks;

                    ?>

                </strong>


            </div>



            <!-- PENDING -->

            <div class="status-row">


                <span>

                    Pending

                </span>


                <strong>

                    <?php

                    echo $pending_tasks;

                    ?>

                </strong>


            </div>


        </div>


    </div>



    <!-- ==================================================
         QUICK ACCESS
         ================================================== -->

    <div class="quick-section">


        <div class="quick-heading">


            <p class="dashboard-small-title">

                QUICK ACCESS

            </p>


            <h2>

                Manage your study

            </h2>


        </div>



        <div class="quick-container">



            <!-- SUBJECT CARD -->

            <a
                href="subjects.php"
                class="quick-card"
            >


                <div class="quick-icon">

                    📚

                </div>


                <h3>

                    Subjects

                </h3>


                <p>

                    Add and manage your subjects.

                </p>


                <span>

                    Manage →

                </span>


            </a>



            <!-- STUDY PLANNER -->

            <a
                href="studyplanner.php"
                class="quick-card"
            >


                <div class="quick-icon">

                    ✓

                </div>


                <h3>

                    Study Planner

                </h3>


                <p>

                    Plan and track your study tasks.

                </p>


                <span>

                    Manage →

                </span>


            </a>



            <!-- MATERIALS -->

            <a
                href="materials.php"
                class="quick-card"
            >


                <div class="quick-icon">

                    📖

                </div>


                <h3>

                    Study Materials

                </h3>


                <p>

                    Keep your study resources organized.

                </p>


                <span>

                    Manage →

                </span>


            </a>



            <!-- PROGRESS -->

            <a
                href="progress.php"
                class="quick-card"
            >


                <div class="quick-icon">

                    📊

                </div>


                <h3>

                    Progress Tracker

                </h3>


                <p>

                    Track your overall study progress.

                </p>


                <span>

                    View Progress →

                </span>


            </a>


        </div>


    </div>



</main>



<!-- ==================================================
     JAVASCRIPT
     ================================================== -->

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
