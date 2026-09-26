<?php

require_once __DIR__ . "/includes/security.php";

include "config/database.php";


// ==========================================
// CHECK LOGIN
// ==========================================

require_login();

$user_id = current_user_id();


// ==========================================
// TOTAL TASKS
// ==========================================

$stmt = mysqli_prepare($conn,
    "SELECT COUNT(*) AS total FROM tasks WHERE user_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$total_tasks = $row['total'];
mysqli_stmt_close($stmt);


// ==========================================
// COMPLETED TASKS
// ==========================================

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


// ==========================================
// PENDING TASKS
// ==========================================

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


// ==========================================
// IN PROGRESS TASKS
// ==========================================

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


// ==========================================
// OVERALL PROGRESS
// ==========================================

if ($total_tasks > 0) {

    $overall_progress =
        round(
            ($completed_tasks / $total_tasks) * 100
        );

} else {

    $overall_progress = 0;

}


// ==========================================
// SUBJECTS
// ==========================================

$subjects_stmt = mysqli_prepare($conn,
    "SELECT id, subject_name
     FROM subjects
     WHERE user_id = ?
     ORDER BY subject_name ASC"
);
mysqli_stmt_bind_param($subjects_stmt, "i", $user_id);
mysqli_stmt_execute($subjects_stmt);
$subjects = mysqli_stmt_get_result($subjects_stmt);
mysqli_stmt_close($subjects_stmt);


// ==========================================
// TASK COUNTS PER SUBJECT (single grouped query)
// ==========================================

$counts_stmt = mysqli_prepare($conn,
    "SELECT subject_id,
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed
     FROM tasks
     WHERE user_id = ?
     GROUP BY subject_id"
);
mysqli_stmt_bind_param($counts_stmt, "i", $user_id);
mysqli_stmt_execute($counts_stmt);
$counts_result = mysqli_stmt_get_result($counts_stmt);

$subject_counts = [];
while ($c = mysqli_fetch_assoc($counts_result)) {
    $subject_counts[$c['subject_id']] = $c;
}
mysqli_stmt_close($counts_stmt);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Progress Tracker - Prepexus</title>


    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>


<body>


<!-- ==========================================
     NAVBAR
     ========================================== -->

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

            echo initial(
                $_SESSION['user_name']
            );

            ?>

        </div>


        <span>

            <?php

            echo htmlspecialchars(
                $_SESSION['user_name']
            );

            ?>

        </span>


    </div>


</nav>



<!-- ==========================================
     SIDEBAR
     ========================================== -->

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



        <!-- CURRENT PAGE -->

        <a
            href="progress.php"
            class="active"
        >

            <span class="sidebar-icon">
                📊
            </span>

            Progress Tracker

        </a>



        <a href="profile.php">

            <span class="sidebar-icon">
                👤
            </span>

            Profile

        </a>



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



<!-- ==========================================
     OVERLAY
     ========================================== -->

<div
    id="overlay"
    class="sidebar-overlay"
    onclick="toggleSidebar()"
>
</div>



<!-- ==========================================
     MAIN CONTENT
     ========================================== -->

<main class="progress-page">


    <!-- BACK BUTTON -->

    <a
        href="dashboard.php"
        class="back-button"
    >

        ← Back to Dashboard

    </a>



    <!-- HEADER -->

    <div class="progress-header">


        <div>

            <p class="dashboard-small-title">

                PERFORMANCE TRACKER

            </p>


            <h1>

                Progress Tracker

            </h1>


            <p>

                Track your overall and subject-wise study progress.

            </p>

        </div>


    </div>



    <!-- ==========================================
         OVERALL PROGRESS
         ========================================== -->

    <div class="overall-progress-card">


        <div class="progress-card-top">


            <div>

                <h2>

                    Overall Progress

                </h2>


                <p>

                    Your completed study tasks.

                </p>

            </div>


            <div class="big-progress-number">

                <?php

                echo $overall_progress;

                ?>%

            </div>


        </div>



        <!-- PROGRESS BAR -->

        <div class="progress-bar">

            <div
                class="progress-fill"
                style="width: <?php echo $overall_progress; ?>%;"
            >

            </div>

        </div>



        <div class="progress-text">

            Completed

            <?php echo $completed_tasks; ?>

            /

            <?php echo $total_tasks; ?>

            Tasks

        </div>


    </div>



    <!-- ==========================================
         STATISTICS
         ========================================== -->

    <div class="progress-statistics">


        <!-- TOTAL -->

        <div class="progress-stat-card">

            <div class="stat-icon total-icon">

                📋

            </div>


            <div>

                <p>

                    Total Tasks

                </p>


                <h2>

                    <?php echo $total_tasks; ?>

                </h2>

            </div>

        </div>



        <!-- COMPLETED -->

        <div class="progress-stat-card">

            <div class="stat-icon completed-icon">

                ✓

            </div>


            <div>

                <p>

                    Completed

                </p>


                <h2>

                    <?php echo $completed_tasks; ?>

                </h2>

            </div>

        </div>



        <!-- IN PROGRESS -->

        <div class="progress-stat-card">

            <div class="stat-icon progress-icon">

                ↻

            </div>


            <div>

                <p>

                    In Progress

                </p>


                <h2>

                    <?php echo $in_progress_tasks; ?>

                </h2>

            </div>

        </div>



        <!-- PENDING -->

        <div class="progress-stat-card">

            <div class="stat-icon pending-icon">

                ◷

            </div>


            <div>

                <p>

                    Pending

                </p>


                <h2>

                    <?php echo $pending_tasks; ?>

                </h2>

            </div>

        </div>


    </div>



    <!-- ==========================================
         SUBJECT PROGRESS
         ========================================== -->

    <div class="subject-progress-card">


        <div class="subject-progress-header">


            <div>

                <h2>

                    Subject-wise Progress

                </h2>


                <p>

                    See how well you are progressing in each subject.

                </p>

            </div>


        </div>



        <div class="subject-progress-list">


        <?php


        if (mysqli_num_rows($subjects) > 0) {


            while (
                $subject =
                mysqli_fetch_assoc($subjects)
            ) {


                $subject_id =
                    $subject['id'];


                // Look up pre-computed counts (no extra queries needed)

                if (isset($subject_counts[$subject_id])) {

                    $subject_total =
                        (int) $subject_counts[$subject_id]['total'];

                    $subject_completed =
                        (int) $subject_counts[$subject_id]['completed'];

                } else {

                    $subject_total = 0;
                    $subject_completed = 0;

                }



                // CALCULATE PERCENTAGE

                if ($subject_total > 0) {

                    $subject_progress =
                        round(
                            (
                                $subject_completed
                                /
                                $subject_total
                            )
                            * 100
                        );

                } else {

                    $subject_progress = 0;

                }


        ?>


            <div class="subject-progress-row">


                <div class="subject-name">

                    <?php

                    echo htmlspecialchars(
                        $subject['subject_name']
                    );

                    ?>

                </div>



                <div class="subject-progress-bar">

                    <div
                        class="subject-progress-fill"
                        style="
                        width:
                        <?php
                        echo $subject_progress;
                        ?>%;
                        "
                    >

                    </div>

                </div>



                <div class="subject-percentage">

                    <?php

                    echo $subject_progress;

                    ?>%

                </div>


            </div>


        <?php

            }

        } else {

        ?>


            <div class="no-subjects">

                No subjects found.

            </div>


        <?php

        }


        ?>


        </div>


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
