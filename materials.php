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



// ==================================================
// ADD MATERIAL
// ==================================================

if (isset($_POST['add_material'])) {

    if (!verify_csrf()) { die('Invalid request.'); }

    $subject_id = $_POST['subject_id'];
    $material_name = $_POST['material_name'];
    $material_type = $_POST['material_type'];
    $material_link = $_POST['material_link'];
    $description = $_POST['description'];


    // Prepared statement -- prevents SQL injection
    $stmt = mysqli_prepare($conn,
        "INSERT INTO materials
            (user_id, subject_id, material_name,
             material_type, material_link, description)
            VALUES (?, ?, ?, ?, ?, ?)"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iissss",
            $user_id, $subject_id, $material_name,
            $material_type, $material_link, $description
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }


    // Prevent duplicate insertion on refresh

    header("Location: materials.php?success=1");

    exit();

}


// ==================================================
// DELETE MATERIAL
// ==================================================

if (isset($_POST['delete_id'])) {

    if (!verify_csrf()) { die('Invalid request.'); }

    $id = $_POST['delete_id'];


    $stmt = mysqli_prepare($conn,
        "DELETE FROM materials
            WHERE id = ?
            AND user_id = ?"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }


    header("Location: materials.php?deleted=1");

    exit();

}


// ==================================================
// EDIT MATERIAL
// ==================================================

$edit_material = null;


if (isset($_GET['edit'])) {

    $id = $_GET['edit'];


    $stmt = mysqli_prepare($conn,
        "SELECT *
            FROM materials
            WHERE id = ?
            AND user_id = ?"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = false;
    }


    if (mysqli_num_rows($result) > 0) {

        $edit_material = mysqli_fetch_assoc($result);

    }

}


// ==================================================
// UPDATE MATERIAL
// ==================================================

if (isset($_POST['update_material'])) {

    if (!verify_csrf()) { die('Invalid request.'); }

    $id = $_POST['id'];

    $subject_id = $_POST['subject_id'];
    $material_name = $_POST['material_name'];
    $material_type = $_POST['material_type'];
    $material_link = $_POST['material_link'];
    $description = $_POST['description'];


    $stmt = mysqli_prepare($conn,
        "UPDATE materials SET
            subject_id = ?,
            material_name = ?,
            material_type = ?,
            material_link = ?,
            description = ?
            WHERE id = ?
            AND user_id = ?"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "issssis",
            $subject_id, $material_name, $material_type,
            $material_link, $description, $id, $user_id
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }


    header("Location: materials.php?updated=1");

    exit();

}


// ==================================================
// GET SUBJECTS
// ==================================================

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


// ==================================================
// GET MATERIALS
// ==================================================

$materials_stmt = mysqli_prepare($conn,
    "SELECT
        materials.*,
        subjects.subject_name
     FROM materials
     JOIN subjects
     ON materials.subject_id = subjects.id
     WHERE materials.user_id = ?
     ORDER BY materials.id DESC"
);
mysqli_stmt_bind_param($materials_stmt, "i", $user_id);
mysqli_stmt_execute($materials_stmt);
$materials = mysqli_stmt_get_result($materials_stmt);
mysqli_stmt_close($materials_stmt);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Study Materials - Prepexus</title>


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
                substr($_SESSION['user_name'], 0, 1)
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



        <!-- CURRENT PAGE -->

        <a
            href="materials.php"
            class="active"
        >

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
     MAIN CONTENT
     ================================================== -->

<main class="materials-page">


    <!-- BACK BUTTON -->

    <a
        href="dashboard.php"
        class="back-button"
    >

        ← Back to Dashboard

    </a>



    <!-- PAGE HEADER -->

    <div class="materials-header">


        <div>


            <p class="dashboard-small-title">

                STUDY RESOURCES

            </p>


            <h1>

                Study Materials

            </h1>


            <p>

                Save and manage your study resources.

            </p>


        </div>



        <button
            class="add-material-button"
            onclick="showMaterialForm()"
            type="button"
        >

            + Add Material

        </button>


    </div>



    <!-- ==================================================
         SUCCESS MESSAGES
         ================================================== -->


    <?php if (isset($_GET['success'])) { ?>

        <div class="material-message success-material">

            Material added successfully.

        </div>

    <?php } ?>



    <?php if (isset($_GET['updated'])) { ?>

        <div class="material-message success-material">

            Material updated successfully.

        </div>

    <?php } ?>



    <?php if (isset($_GET['deleted'])) { ?>

        <div class="material-message delete-material">

            Material deleted successfully.

        </div>

    <?php } ?>



    <!-- ==================================================
         ADD / EDIT FORM
         ================================================== -->

    <div
        id="materialForm"
        class="material-form

        <?php

        if ($edit_material != null) {

            echo 'show';

        }

        ?>"
    >


        <div class="material-form-header">


            <div>


                <p class="form-label-small">

                    STUDY RESOURCES

                </p>


                <h2>

                    <?php

                    if ($edit_material != null) {

                        echo "Edit Material";

                    } else {

                        echo "Add New Material";

                    }

                    ?>

                </h2>


            </div>


            <button
                type="button"
                class="material-close-button"
                onclick="hideMaterialForm()"
            >

                ×

            </button>


        </div>



        <!-- FORM -->

        <form method="POST">
                <input type="hidden" name="csrf_token"
                     value="<?php echo $csrf_token; ?>">


            <?php if ($edit_material != null) { ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo $edit_material['id']; ?>"
                >

            <?php } ?>



            <!-- MATERIAL NAME -->

            <div class="material-input-group">


                <label>

                    Material Name

                </label>


                <input
                    type="text"
                    name="material_name"
                    placeholder="Example: PHP Complete Notes"
                    value="<?php

                    if ($edit_material != null) {

                        echo htmlspecialchars(
                            $edit_material['material_name']
                        );

                    }

                    ?>"
                    required
                >


            </div>



            <!-- SUBJECT -->

            <div class="material-input-group">


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

                    while (
                        $subject =
                        mysqli_fetch_assoc($subjects)
                    ) {

                    ?>


                        <option
                            value="<?php echo $subject['id']; ?>"

                            <?php

                            if (
                                $edit_material != null &&
                                $edit_material['subject_id']
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



            <!-- TYPE -->

            <div class="material-input-group">


                <label>

                    Material Type

                </label>


                <select
                    name="material_type"
                    required
                >


                    <option
                        value="PDF"

                        <?php

                        if (
                            $edit_material != null &&
                            $edit_material['material_type']
                            == 'PDF'
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        PDF

                    </option>


                    <option
                        value="Video"

                        <?php

                        if (
                            $edit_material != null &&
                            $edit_material['material_type']
                            == 'Video'
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Video

                    </option>


                    <option
                        value="Website"

                        <?php

                        if (
                            $edit_material != null &&
                            $edit_material['material_type']
                            == 'Website'
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Website

                    </option>


                    <option
                        value="Notes"

                        <?php

                        if (
                            $edit_material != null &&
                            $edit_material['material_type']
                            == 'Notes'
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Notes

                    </option>


                </select>


            </div>



            <!-- LINK -->

            <div class="material-input-group">


                <label>

                    Material Link

                </label>


                <input
                    type="url"
                    name="material_link"
                    placeholder="https://example.com"
                    value="<?php

                    if ($edit_material != null) {

                        echo htmlspecialchars(
                            $edit_material['material_link']
                        );

                    }

                    ?>"
                    required
                >


            </div>



            <!-- DESCRIPTION -->

            <div class="material-input-group">


                <label>

                    Description

                </label>


                <textarea
                    name="description"
                    placeholder="Describe this material..."
                    rows="4"
                ><?php

                if ($edit_material != null) {

                    echo htmlspecialchars(
                        $edit_material['description']
                    );

                }

                ?></textarea>


            </div>



            <!-- SAVE / UPDATE -->

            <?php if ($edit_material != null) { ?>


                <button
                    type="submit"
                    name="update_material"
                    class="save-material-button"
                >

                    Update Material

                </button>


            <?php } else { ?>


                <button
                    type="submit"
                    name="add_material"
                    class="save-material-button"
                >

                    Save Material

                </button>


            <?php } ?>


        </form>


    </div>



    <!-- ==================================================
         MATERIAL LIST
         ================================================== -->

    <div class="material-list">


        <div class="material-list-header">


            <div>


                <h2>

                    My Study Materials

                </h2>


                <p>

                    All your saved study resources.

                </p>


            </div>


        </div>



        <!-- TABLE -->

        <div class="material-table-container">


            <table class="material-table">


                <thead>

                    <tr>

                        <th>No</th>

                        <th>Material</th>

                        <th>Subject</th>

                        <th>Type</th>

                        <th>Description</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                <?php

                $number = 1;


                while (
                    $row =
                    mysqli_fetch_assoc($materials)
                ) {

                ?>


                    <tr>


                        <!-- NUMBER -->

                        <td>

                            <?php

                            echo $number;

                            ?>

                        </td>



                        <!-- MATERIAL -->

                        <td>


                            <div class="material-title">

                                <?php

                                echo htmlspecialchars(
                                    $row['material_name']
                                );

                                ?>

                            </div>


                        </td>



                        <!-- SUBJECT -->

                        <td>


                            <span class="material-subject">

                                <?php

                                echo htmlspecialchars(
                                    $row['subject_name']
                                );

                                ?>

                            </span>


                        </td>



                        <!-- TYPE -->

                        <td>


                            <span class="material-type">

                                <?php

                                echo htmlspecialchars(
                                    $row['material_type']
                                );

                                ?>

                            </span>


                        </td>



                        <!-- DESCRIPTION -->

                        <td>


                            <span class="material-description">

                                <?php

                                echo htmlspecialchars(
                                    $row['description']
                                );

                                ?>

                            </span>


                        </td>



                        <!-- ACTION -->

                        <td>


                            <div class="material-actions">


                                <!-- OPEN -->

                                <a
                                    href="<?php echo htmlspecialchars($row['material_link']); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="material-open-button"
                                >

                                    Open

                                </a>



                                <!-- EDIT -->

                                <a
                                    href="materials.php?edit=<?php echo $row['id']; ?>"
                                    class="material-edit-button"
                                >

                                    Edit

                                </a>



                                <!-- DELETE -->

                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this material?');"><input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>"><button type="submit" class="material-delete-button" style="background:none;border:none;padding:0;cursor:pointer;color:#df5353;font-weight:bold;font-size:12px;">Delete</button></form>


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


    </div>


</main>



<!-- ==================================================
     JAVASCRIPT
     ================================================== -->

<script>


function showMaterialForm() {

    document
        .getElementById("materialForm")
        .classList.add("show");

}


function hideMaterialForm() {

    document
        .getElementById("materialForm")
        .classList.remove("show");

}


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
