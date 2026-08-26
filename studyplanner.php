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
// ADD TASK
// ======================================================

if (isset($_POST['add_task'])) {

    if (!verify_csrf()) { die('Invalid request.'); }

    $subject_id = $_POST['subject_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $study_date = $_POST['study_date'];
    $due_date = $_POST['due_date'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];


    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO tasks
        (user_id, subject_id, title, description, study_date, due_date, priority, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );


    mysqli_stmt_bind_param(
        $stmt,
        "iissssss",
        $user_id,
        $subject_id,
        $title,
        $description,
        $study_date,
        $due_date,
        $priority,
        $status
    );


    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);


    // Prevent duplicate insertion after refresh

    header("Location: studyplanner.php?success=1");
    exit();

}


// ======================================================
// UPDATE TASK
// ======================================================

if (isset($_POST['update_task'])) {

    if (!verify_csrf()) { die('Invalid request.'); }

    $task_id = $_POST['task_id'];

    $subject_id = $_POST['subject_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $study_date = $_POST['study_date'];
    $due_date = $_POST['due_date'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];


    $stmt = mysqli_prepare(
        $conn,

        "UPDATE tasks SET
            subject_id = ?,
            title = ?,
            description = ?,
            study_date = ?,
            due_date = ?,
            priority = ?,
            status = ?
        WHERE id = ?
        AND user_id = ?"
    );


    mysqli_stmt_bind_param(
        $stmt,
        "issssssii",
        $subject_id,
        $title,
        $description,
        $study_date,
        $due_date,
        $priority,
        $status,
        $task_id,
        $user_id
    );


    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);


    header("Location: studyplanner.php?updated=1");
    exit();

}


// ======================================================
// DELETE TASK
// ======================================================

if (isset($_POST['delete_id'])) {

    if (!verify_csrf()) { die('Invalid request.'); }

    $task_id = $_POST['delete_id'];


    $stmt = mysqli_prepare(
        $conn,

        "DELETE FROM tasks
         WHERE id = ?
         AND user_id = ?"
    );


    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $task_id,
        $user_id
    );


    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);


    header("Location: studyplanner.php?deleted=1");
    exit();

}


// ======================================================
// MARK TASK AS COMPLETED
// ======================================================

if (isset($_POST['complete_id'])) {

    if (!verify_csrf()) { die('Invalid request.'); }

    $task_id = $_POST['complete_id'];

    $status = "Completed";


    $stmt = mysqli_prepare(
        $conn,

        "UPDATE tasks
         SET status = ?
         WHERE id = ?
         AND user_id = ?"
    );


    mysqli_stmt_bind_param(
        $stmt,
        "sii",
        $status,
        $task_id,
        $user_id
    );


    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);


    header("Location: studyplanner.php?status=1");
    exit();

}


// ======================================================
// GET SUBJECTS
// ======================================================

$subject_sql = "
    SELECT id, subject_name
    FROM subjects
    WHERE user_id = ?
    ORDER BY subject_name ASC
";


$stmt = mysqli_prepare(
    $conn,
    $subject_sql
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);


mysqli_stmt_execute($stmt);

$subject_result =
    mysqli_stmt_get_result($stmt);

mysqli_stmt_close($stmt);


// ======================================================
// EDIT TASK
// ======================================================

$edit_task = null;


if (isset($_GET['edit'])) {

    $edit_id = $_GET['edit'];


    $stmt = mysqli_prepare(
        $conn,

        "SELECT
            id,
            subject_id,
            title,
            description,
            study_date,
            due_date,
            priority,
            status
         FROM tasks
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

        $edit_task =
            mysqli_fetch_assoc($edit_result);

    }


    mysqli_stmt_close($stmt);

}


// ======================================================
// GET ALL TASKS
// ======================================================

$task_sql = "
    SELECT
        tasks.id,
        tasks.user_id,
        tasks.subject_id,
        tasks.title,
        tasks.description,
        tasks.study_date,
        tasks.due_date,
        tasks.priority,
        tasks.status,
        subjects.subject_name

    FROM tasks

    INNER JOIN subjects
        ON tasks.subject_id = subjects.id

    WHERE tasks.user_id = ?

    ORDER BY tasks.due_date ASC,
             tasks.id DESC
";


$stmt = mysqli_prepare(
    $conn,
    $task_sql
);


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);


mysqli_stmt_execute($stmt);

$task_result =
    mysqli_stmt_get_result($stmt);

mysqli_stmt_close($stmt);


$total_tasks =
    mysqli_num_rows($task_result);

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Study Planner - Prepexus</title>


    <link
        rel="stylesheet"
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



        <a
            href="studyplanner.php"
            class="active"
        >

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



        <a href="profile.php">

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

<main class="planner-page">


    <!-- ==================================================
         PAGE HEADER
         ================================================== -->

    <div class="planner-header">


        <div>


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

                Study Planner

            </h1>


            <p>

                Plan your study tasks and track your progress.

            </p>


        </div>



        <!-- ADD TASK -->

        <button
            class="add-task-button"
            onclick="showTaskForm()"
            type="button"
        >

            + Add Task

        </button>


    </div>



    <!-- ==================================================
         MESSAGES
         ================================================== -->

    <?php if (isset($_GET['success'])) { ?>

        <div class="planner-message success-message">

            Task added successfully.

        </div>

    <?php } ?>


    <?php if (isset($_GET['updated'])) { ?>

        <div class="planner-message success-message">

            Task updated successfully.

        </div>

    <?php } ?>


    <?php if (isset($_GET['deleted'])) { ?>

        <div class="planner-message delete-message">

            Task deleted successfully.

        </div>

    <?php } ?>


    <?php if (isset($_GET['status'])) { ?>

        <div class="planner-message success-message">

            Task marked as completed.

        </div>

    <?php } ?>



    <!-- ==================================================
         ADD / EDIT FORM
         ================================================== -->

    <div
        id="task-form"
        class="task-form

        <?php

        if ($edit_task != null) {

            echo 'show';

        }

        ?>"
    >


        <!-- FORM HEADER -->

        <div class="task-form-header">


            <div>

                <p class="form-label-small">

                    STUDY PLANNER

                </p>


                <h2>

                    <?php

                    if ($edit_task != null) {

                        echo "Edit Study Task";

                    } else {

                        echo "Add New Study Task";

                    }

                    ?>

                </h2>

            </div>


            <button
                class="task-form-close"
                onclick="hideTaskForm()"
                type="button"
            >

                ×

            </button>


        </div>



        <!-- FORM -->

        <form method="POST">
                <input type="hidden" name="csrf_token"
                     value="<?php echo $csrf_token; ?>">


            <?php if ($edit_task != null) { ?>

                <input
                    type="hidden"
                    name="task_id"
                    value="<?php
                    echo $edit_task['id'];
                    ?>"
                >

            <?php } ?>



            <!-- ROW 1 -->

            <div class="task-form-grid">


                <!-- TITLE -->

                <div class="task-input-group">


                    <label>

                        Task Title

                    </label>


                    <input
                        type="text"
                        name="title"
                        placeholder="Example: Complete PHP CRUD"

                        value="<?php

                        if ($edit_task != null) {

                            echo htmlspecialchars(
                                $edit_task['title']
                            );

                        }

                        ?>"

                        required
                    >


                </div>



                <!-- SUBJECT -->

                <div class="task-input-group">


                    <label>

                        Subject

                    </label>


                    <select
                        name="subject_id"
                        required
                    >


                        <option value="">

                            Select Subject

                        </option>


                        <?php

                        mysqli_data_seek(
                            $subject_result,
                            0
                        );


                        while (
                            $subject =
                            mysqli_fetch_assoc(
                                $subject_result
                            )
                        ) {

                        ?>


                            <option
                                value="<?php
                                echo $subject['id'];
                                ?>"

                                <?php

                                if (
                                    $edit_task != null
                                    &&
                                    $edit_task['subject_id']
                                    == $subject['id']
                                ) {

                                    echo "selected";

                                }

                                ?>
                            >

                                <?php

                                echo htmlspecialchars(
                                    $subject['subject_name']
                                );

                                ?>

                            </option>


                        <?php

                        }

                        ?>


                    </select>


                </div>


            </div>



            <!-- ROW 2 -->

            <div class="task-form-grid">


                <!-- STUDY DATE -->

                <div class="task-input-group">


                    <label>

                        Study Date

                    </label>


                    <input
                        type="date"
                        name="study_date"

                        value="<?php

                        if ($edit_task != null) {

                            echo $edit_task['study_date'];

                        }

                        ?>"

                        required
                    >


                </div>



                <!-- DUE DATE -->

                <div class="task-input-group">


                    <label>

                        Due Date

                    </label>


                    <input
                        type="date"
                        name="due_date"

                        value="<?php

                        if ($edit_task != null) {

                            echo $edit_task['due_date'];

                        }

                        ?>"

                        required
                    >


                </div>


            </div>



            <!-- ROW 3 -->

            <div class="task-form-grid">


                <!-- PRIORITY -->

                <div class="task-input-group">


                    <label>

                        Priority

                    </label>


                    <select
                        name="priority"
                        required
                    >


                        <option
                            value="High"

                            <?php

                            if (
                                $edit_task != null
                                &&
                                $edit_task['priority']
                                == "High"
                            ) {

                                echo "selected";

                            }

                            ?>
                        >

                            High

                        </option>


                        <option
                            value="Medium"

                            <?php

                            if (
                                $edit_task != null
                                &&
                                $edit_task['priority']
                                == "Medium"
                            ) {

                                echo "selected";

                            }

                            ?>
                        >

                            Medium

                        </option>


                        <option
                            value="Low"

                            <?php

                            if (
                                $edit_task != null
                                &&
                                $edit_task['priority']
                                == "Low"
                            ) {

                                echo "selected";

                            }

                            ?>
                        >

                            Low

                        </option>


                    </select>


                </div>



                <!-- STATUS -->

                <div class="task-input-group">


                    <label>

                        Status

                    </label>


                    <select
                        name="status"
                        required
                    >


                        <option
                            value="Pending"

                            <?php

                            if (
                                $edit_task == null
                                ||
                                $edit_task['status']
                                == "Pending"
                            ) {

                                echo "selected";

                            }

                            ?>
                        >

                            Pending

                        </option>


                        <option
                            value="In Progress"

                            <?php

                            if (
                                $edit_task != null
                                &&
                                $edit_task['status']
                                == "In Progress"
                            ) {

                                echo "selected";

                            }

                            ?>
                        >

                            In Progress

                        </option>


                        <option
                            value="Completed"

                            <?php

                            if (
                                $edit_task != null
                                &&
                                $edit_task['status']
                                == "Completed"
                            ) {

                                echo "selected";

                            }

                            ?>
                        >

                            Completed

                        </option>


                    </select>


                </div>


            </div>



            <!-- DESCRIPTION -->

            <div class="task-input-group full-width">


                <label>

                    Description

                </label>


                <textarea
                    name="description"
                    placeholder="Describe what you need to study..."
                    rows="4"
                ><?php

                if ($edit_task != null) {

                    echo htmlspecialchars(
                        $edit_task['description']
                    );

                }

                ?></textarea>


            </div>



            <!-- FORM BUTTON -->

            <?php if ($edit_task != null) { ?>


                <button
                    type="submit"
                    name="update_task"
                    class="save-task-button"
                >

                    Update Task

                </button>


            <?php } else { ?>


                <button
                    type="submit"
                    name="add_task"
                    class="save-task-button"
                >

                    Save Task

                </button>


            <?php } ?>


        </form>


    </div>



    <!-- ==================================================
         TASK LIST
         ================================================== -->

    <div class="task-list">


        <!-- LIST HEADER -->

        <div class="task-list-header">


            <div>

                <h2>

                    My Study Tasks

                </h2>


                <p>

                    All your planned study activities.

                </p>

            </div>


            <span class="task-count">

                <?php echo $total_tasks; ?>

                <?php

                if ($total_tasks == 1) {

                    echo "Task";

                } else {

                    echo "Tasks";

                }

                ?>

            </span>


        </div>



        <!-- ==================================================
             TABLE
             ================================================== -->

        <?php if ($total_tasks > 0) { ?>


            <div class="task-table-container">


                <table class="task-table">


                    <thead>

                        <tr>

                            <th>No</th>

                            <th>Task</th>

                            <th>Subject</th>

                            <th>Study Date</th>

                            <th>Due Date</th>

                            <th>Priority</th>

                            <th>Status</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php

                    $number = 1;


                    while (
                        $row =
                        mysqli_fetch_assoc(
                            $task_result
                        )
                    ) {


                        $title =
                            isset($row['title'])
                            ? $row['title']
                            : '';


                        $description =
                            isset($row['description'])
                            ? $row['description']
                            : '';


                        $subject_name =
                            isset($row['subject_name'])
                            ? $row['subject_name']
                            : '';


                        $study_date =
                            isset($row['study_date'])
                            ? $row['study_date']
                            : '';


                        $due_date =
                            isset($row['due_date'])
                            ? $row['due_date']
                            : '';


                        $priority =
                            isset($row['priority'])
                            ? $row['priority']
                            : '';


                        $status =
                            isset($row['status'])
                            ? $row['status']
                            : '';

                    ?>


                        <tr>


                            <!-- NO -->

                            <td>

                                <?php

                                echo $number;

                                ?>

                            </td>



                            <!-- TASK -->

                            <td>


                                <div class="task-title">

                                    <?php

                                    echo htmlspecialchars(
                                        $title
                                    );

                                    ?>

                                </div>


                                <?php

                                if (
                                    $description != ""
                                ) {

                                ?>

                                    <div
                                        class="task-description"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $description
                                        );

                                        ?>

                                    </div>

                                <?php

                                }

                                ?>

                            </td>



                            <!-- SUBJECT -->

                            <td>

                                <span
                                    class="task-subject"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $subject_name
                                    );

                                    ?>

                                </span>

                            </td>



                            <!-- STUDY DATE -->

                            <td>

                                <?php

                                if (
                                    !empty($study_date)
                                ) {

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $study_date
                                        )
                                    );

                                } else {

                                    echo "-";

                                }

                                ?>

                            </td>



                            <!-- DUE DATE -->

                            <td>

                                <?php

                                if (
                                    !empty($due_date)
                                ) {

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $due_date
                                        )
                                    );

                                } else {

                                    echo "-";

                                }

                                ?>

                            </td>



                            <!-- PRIORITY -->

                            <td>

                                <?php

                                $priority_class =
                                    strtolower(
                                        $priority
                                    );

                                ?>


                                <span
                                    class="priority-badge
                                    priority-<?php
                                    echo $priority_class;
                                    ?>"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $priority
                                    );

                                    ?>

                                </span>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <?php

                                $status_class =
                                    strtolower(
                                        str_replace(
                                            " ",
                                            "-",
                                            $status
                                        )
                                    );

                                ?>


                                <span
                                    class="status-badge
                                    status-<?php
                                    echo $status_class;
                                    ?>"
                                >

                                    <?php

                                    if (
                                        $status
                                        == "Completed"
                                    ) {

                                        echo "✓ ";

                                    }

                                    echo htmlspecialchars(
                                        $status
                                    );

                                    ?>

                                </span>

                            </td>



                            <!-- ACTION -->

                            <td>

                                <div
                                    class="task-actions"
                                >


                                    <!-- EDIT -->

                                    <a
                                        href="studyplanner.php?edit=<?php
                                        echo $row['id'];
                                        ?>"
                                        class="task-edit-button"
                                    >

                                        Edit

                                    </a>



                                    <!-- COMPLETE -->

                                    <?php

                                    if (
                                        $status
                                        != "Completed"
                                    ) {

                                    ?>

                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Mark this task as completed?');"><input type="hidden" name="complete_id" value="<?php echo $row['id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>"><button type="submit" class="task-complete-button" style="background:none;border:none;padding:0;cursor:pointer;color:#21864b;font-weight:bold;font-size:14px;">&#10003;</button></form>

                                    <?php

                                    }

                                    ?>



                                    <!-- DELETE -->

                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this task?');"><input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>"><button type="submit" class="task-delete-button" style="background:none;border:none;padding:0;cursor:pointer;color:#df5353;font-weight:bold;font-size:11px;">Delete</button></form>


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


            <!-- EMPTY STATE -->

            <div class="empty-task">


                <div class="empty-task-icon">

                    ✓

                </div>


                <h3>

                    No study tasks yet

                </h3>


                <p>

                    Start planning your study by adding your first task.

                </p>


                <button
                    onclick="showTaskForm()"
                    class="empty-task-button"
                    type="button"
                >

                    + Add Your First Task

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

    const sidebar =
        document.getElementById("sidebar");

    const overlay =
        document.getElementById("overlay");


    sidebar.classList.toggle("open");

    overlay.classList.toggle("show");

}



// ======================================================
// SHOW TASK FORM
// ======================================================

function showTaskForm() {

    document
        .getElementById("task-form")
        .classList.add("show");

}



// ======================================================
// HIDE TASK FORM
// ======================================================

function hideTaskForm() {

    document
        .getElementById("task-form")
        .classList.remove("show");

}


</script>


</body>

</html>
