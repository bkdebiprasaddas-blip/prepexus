<?php

session_start();

include "config/database.php";


// ======================================================
// CHECK LOGIN
// ======================================================

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



// ======================================================
// ADD SUBJECT
// ======================================================

if (isset($_POST['add_subject'])) {

    if (!verify_csrf()) { die('Invalid request.'); }

    $subject_name = trim($_POST['subject_name']);
    $subject_code = trim($_POST['subject_code']);
    $credit = $_POST['credit'];
    $description = trim($_POST['description']);


    $stmt = mysqli_prepare(
        $conn,

        "INSERT INTO subjects
        (
            user_id,
            subject_name,
            subject_code,
            credit,
            description
        )
        VALUES (?, ?, ?, ?, ?)"
    );


    mysqli_stmt_bind_param(
        $stmt,
        "issis",
        $user_id,
        $subject_name,
        $subject_code,
        $credit,
        $description
    );


    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        header("Location: subjects.php?success=1");

        exit();

    }


    mysqli_stmt_close($stmt);

}


// ======================================================
// UPDATE SUBJECT
// ======================================================

if (isset($_POST['update_subject'])) {

    if (!verify_csrf()) { die('Invalid request.'); }

    $id = $_POST['id'];

    $subject_name = trim($_POST['subject_name']);
    $subject_code = trim($_POST['subject_code']);
    $credit = $_POST['credit'];
    $description = trim($_POST['description']);


    $stmt = mysqli_prepare(
        $conn,

        "UPDATE subjects SET

            subject_name = ?,
            subject_code = ?,
            credit = ?,
            description = ?

         WHERE id = ?
         AND user_id = ?"
    );


    mysqli_stmt_bind_param(
        $stmt,
        "ssisii",
        $subject_name,
        $subject_code,
        $credit,
        $description,
        $id,
        $user_id
    );


    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        header("Location: subjects.php?updated=1");

        exit();

    }


    mysqli_stmt_close($stmt);

}


// ======================================================
// DELETE SUBJECT
// ======================================================

if (isset($_POST['delete_id'])) {

    if (!verify_csrf()) { die('Invalid request.'); }

    $id = $_POST['delete_id'];


    $stmt = mysqli_prepare(
        $conn,

        "DELETE FROM subjects

         WHERE id = ?
         AND user_id = ?"
    );


    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $id,
        $user_id
    );


    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        header("Location: subjects.php?deleted=1");

        exit();

    }


    mysqli_stmt_close($stmt);

}


// ======================================================
// EDIT SUBJECT
// ======================================================

$edit_subject = null;


if (isset($_GET['edit'])) {

    $edit_id = $_GET['edit'];


    $stmt = mysqli_prepare(
        $conn,

        "SELECT
            id,
            subject_name,
            subject_code,
            credit,
            description

         FROM subjects

         WHERE id = ?
         AND user_id = ?"
    );


    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $edit_id,
        $user_id
    );


    mysqli_stmt_execute($stmt);


    $edit_result =
        mysqli_stmt_get_result($stmt);


    if (
        mysqli_num_rows($edit_result) > 0
    ) {

        $edit_subject =
            mysqli_fetch_assoc($edit_result);

    }


    mysqli_stmt_close($stmt);

}


// ======================================================
// GET ALL SUBJECTS
// ======================================================

$stmt = mysqli_prepare(
    $conn,

    "SELECT
        id,
        subject_name,
        subject_code,
        credit,
        description

     FROM subjects

     WHERE user_id = ?

     ORDER BY id DESC"
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);


mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


$total_subjects =
    mysqli_num_rows($result);


mysqli_stmt_close($stmt);

?>


<!DOCTYPE html>

<html lang="en">


<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Subjects - Prepexus</title>


    <link
        rel="stylesheet"
        type="text/css"
        href="css/style.css"
    >

</head>


<body>


<!-- ======================================================
     HEADER
     ====================================================== -->

<nav class="dashboard-navbar">


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



    <!-- USER -->

    <div class="dashboard-user">


        <div class="user-avatar">

            <?php

            echo strtoupper(
                substr(
                    $_SESSION['user_name'],
                    0,
                    1
                )
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



<!-- ======================================================
     SIDEBAR
     ====================================================== -->

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

        <a href="dashboard.php">

            <span class="sidebar-icon">
                🏠
            </span>

            Dashboard

        </a>



        <!-- SUBJECTS -->

        <a
            href="subjects.php"
            class="active"
        >

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



        <!-- STUDY MATERIALS -->

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



<!-- ======================================================
     OVERLAY
     ====================================================== -->

<div
    id="overlay"
    class="sidebar-overlay"
    onclick="toggleSidebar()"
>
</div>



<!-- ======================================================
     MAIN CONTENT
     ====================================================== -->

<main class="subjects-page">


    <!-- ==================================================
         PAGE HEADER
         ================================================== -->

    <div class="page-header">


        <div>


            <!-- BACK BUTTON -->

            <a
                href="dashboard.php"
                class="back-button"
            >

                ← Back to Dashboard

            </a>


            <p class="dashboard-small-title">

                STUDY MANAGEMENT

            </p>


            <h1>

                My Subjects

            </h1>


            <p>

                Add and manage your study subjects.

            </p>


        </div>



        <!-- ADD SUBJECT BUTTON -->

        <button
            class="add-subject-button"
            onclick="showForm()"
            type="button"
        >

            + Add Subject

        </button>


    </div>



    <!-- ==================================================
         SUCCESS / UPDATE / DELETE MESSAGES
         ================================================== -->


    <?php if (isset($_GET['success'])) { ?>

        <div class="subject-message">

            Subject added successfully.

        </div>

    <?php } ?>


    <?php if (isset($_GET['updated'])) { ?>

        <div class="subject-message">

            Subject updated successfully.

        </div>

    <?php } ?>


    <?php if (isset($_GET['deleted'])) { ?>

        <div class="subject-message">

            Subject deleted successfully.

        </div>

    <?php } ?>



    <!-- ==================================================
         ADD / EDIT FORM
         ================================================== -->

    <div
        id="subject-form"
        class="subject-form

        <?php

        if ($edit_subject != null) {

            echo " show";

        }

        ?>"
    >


        <!-- FORM HEADER -->

        <div class="form-heading">


            <h2>

                <?php

                if ($edit_subject != null) {

                    echo "Edit Subject";

                } else {

                    echo "Add New Subject";

                }

                ?>

            </h2>


            <button
                class="form-close"
                onclick="hideForm()"
                type="button"
            >

                ×

            </button>


        </div>



        <!-- FORM -->

        <form method="POST">
                <input type="hidden" name="csrf_token"
                     value="<?php echo $csrf_token; ?>">


            <!-- EDIT ID -->

            <?php if ($edit_subject != null) { ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?php
                    echo $edit_subject['id'];
                    ?>"
                >

            <?php } ?>



            <!-- FIRST ROW -->

            <div class="subject-form-row">


                <!-- SUBJECT NAME -->

                <div class="subject-form-group">


                    <label>

                        Subject Name

                    </label>


                    <input
                        type="text"
                        name="subject_name"
                        placeholder="Example: PHP"

                        value="<?php

                        if ($edit_subject != null) {

                            echo htmlspecialchars(
                                $edit_subject['subject_name']
                            );

                        }

                        ?>"

                        required
                    >


                </div>



                <!-- SUBJECT CODE -->

                <div class="subject-form-group">


                    <label>

                        Subject Code

                    </label>


                    <input
                        type="text"
                        name="subject_code"
                        placeholder="Example: WFS101"

                        value="<?php

                        if ($edit_subject != null) {

                            echo htmlspecialchars(
                                $edit_subject['subject_code']
                            );

                        }

                        ?>"

                        required
                    >


                </div>


            </div>



            <!-- SECOND ROW -->

            <div class="subject-form-row">


                <!-- CREDIT -->

                <div class="subject-form-group">


                    <label>

                        Credit

                    </label>


                    <input
                        type="number"
                        name="credit"
                        placeholder="Example: 4"
                        min="1"

                        value="<?php

                        if ($edit_subject != null) {

                            echo htmlspecialchars(
                                $edit_subject['credit']
                            );

                        }

                        ?>"

                        required
                    >


                </div>



                <!-- DESCRIPTION -->

                <div class="subject-form-group">


                    <label>

                        Description

                    </label>


                    <input
                        type="text"
                        name="description"
                        placeholder="Short description"

                        value="<?php

                        if ($edit_subject != null) {

                            echo htmlspecialchars(
                                $edit_subject['description']
                            );

                        }

                        ?>"
                    >


                </div>


            </div>



            <!-- SAVE / UPDATE -->

            <?php if ($edit_subject != null) { ?>


                <button
                    type="submit"
                    name="update_subject"
                    class="save-subject-button"
                >

                    Update Subject

                </button>


            <?php } else { ?>


                <button
                    type="submit"
                    name="add_subject"
                    class="save-subject-button"
                >

                    Save Subject

                </button>


            <?php } ?>


        </form>


    </div>



    <!-- ==================================================
         SUBJECT LIST
         ================================================== -->

    <div class="subject-list">


        <!-- LIST HEADER -->

        <div class="subject-list-header">


            <div>


                <h2>

                    Your Subjects

                </h2>


                <p>

                    All subjects added to your study tracker.

                </p>


            </div>


            <span class="subject-count">

                <?php

                echo $total_subjects;

                ?>

                <?php

                if ($total_subjects == 1) {

                    echo "Subject";

                } else {

                    echo "Subjects";

                }

                ?>

            </span>


        </div>



        <!-- ==================================================
             SUBJECT TABLE
             ================================================== -->

        <?php if ($total_subjects > 0) { ?>


            <div class="table-container">


                <table>


                    <thead>

                        <tr>


                            <th>

                                No

                            </th>


                            <th>

                                Subject

                            </th>


                            <th>

                                Code

                            </th>


                            <th>

                                Credit

                            </th>


                            <th>

                                Description

                            </th>


                            <th>

                                Action

                            </th>


                        </tr>

                    </thead>



                    <tbody>


                    <?php

                    $number = 1;


                    while (
                        $row =
                        mysqli_fetch_assoc($result)
                    ) {

                    ?>


                        <tr>


                            <!-- NO -->

                            <td>

                                <?php

                                echo $number;

                                ?>

                            </td>



                            <!-- SUBJECT -->

                            <td>


                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $row['subject_name']
                                    );

                                    ?>

                                </strong>


                            </td>



                            <!-- CODE -->

                            <td>


                                <span
                                    class="subject-code"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $row['subject_code']
                                    );

                                    ?>

                                </span>


                            </td>



                            <!-- CREDIT -->

                            <td>


                                <span
                                    class="credit-badge"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $row['credit']
                                    );

                                    ?>

                                </span>


                            </td>



                            <!-- DESCRIPTION -->

                            <td>

                                <?php

                                if (
                                    !empty(
                                        $row['description']
                                    )
                                ) {

                                    echo htmlspecialchars(
                                        $row['description']
                                    );

                                } else {

                                    echo "-";

                                }

                                ?>

                            </td>



                            <!-- ACTION -->

                            <td>


                                <div
                                    class="subject-actions"
                                >


                                    <!-- EDIT -->

                                    <a
                                        href="subjects.php?edit=<?php
                                        echo $row['id'];
                                        ?>"
                                        class="edit-button"
                                    >

                                        Edit

                                    </a>



                                    <!-- DELETE -->

                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this subject?');"><input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>"><button type="submit" class="delete-button" style="background:none;border:none;padding:0;cursor:pointer;color:#df5353;font-weight:bold;font-size:13px;">Delete</button></form>


                                </div>


                            </td>


                        </tr>


                    <?php

                        $number++;

                    }

                    ?>


                    </tbody>


                </table>


            </div>


        <?php } else { ?>


            <!-- ==================================================
                 EMPTY STATE
                 ================================================== -->

            <div class="empty-subject">


                <div class="empty-icon">

                    📚

                </div>


                <h3>

                    No subjects yet

                </h3>


                <p>

                    Start by adding your first subject.

                </p>


                <button
                    onclick="showForm()"
                    class="empty-button"
                    type="button"
                >

                    + Add Your First Subject

                </button>


            </div>


        <?php } ?>


    </div>


</main>



<!-- ======================================================
     JAVASCRIPT
     ====================================================== -->

<script>


// ======================================================
// SIDEBAR
// ======================================================

function toggleSidebar() {

    var sidebar =
        document.getElementById("sidebar");

    var overlay =
        document.getElementById("overlay");


    sidebar.classList.toggle("open");

    overlay.classList.toggle("show");

}



// ======================================================
// SHOW SUBJECT FORM
// ======================================================

function showForm() {

    document
        .getElementById("subject-form")
        .classList.add("show");

}



// ======================================================
// HIDE SUBJECT FORM
// ======================================================

function hideForm() {

    document
        .getElementById("subject-form")
        .classList.remove("show");

}


</script>


</body>

</html>
