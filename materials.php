<?php

require_once __DIR__ . "/includes/security.php";

include "config/database.php";

// ==================================================
// CHECK LOGIN
// ==================================================

require_login();

$user_id = current_user_id();


// CSRF token
$csrf_token = csrf_token();


$material_errors = array(
    'subject' => "Please choose one of your own subjects.",
    'name'    => "Please enter a material name (150 characters max).",
    'type'    => "Please choose a valid material type.",
    'link'    => "Please enter a valid http:// or https:// link.",
    'desc'    => "Description must be 255 characters or fewer.",
    'save'    => "Could not save the material. Please try again.",
);

$message = "";

if (isset($_GET['err']) && isset($material_errors[$_GET['err']])) {
    $message = $material_errors[$_GET['err']];
}


/**
 * Collect and validate the fields shared by the add and update
 * handlers. Returns null and sets $error_code on the first problem.
 */
function collect_material_input($conn, $user_id, &$error_code)
{
    $error_code = "";

    $subject_id    = (int) ($_POST['subject_id'] ?? 0);
    $material_name = trim($_POST['material_name'] ?? '');
    $material_type = trim($_POST['material_type'] ?? '');
    $material_link = trim($_POST['material_link'] ?? '');
    $description   = trim($_POST['description'] ?? '');

    // The subject must belong to this user. Without this check a
    // material can be attached to somebody else's subject, and the
    // listing JOIN then discloses that subject's name.
    if (!user_owns_subject($conn, $user_id, $subject_id)) {
        $error_code = 'subject';
        return null;
    }

    if ($material_name === '' || strlen($material_name) > 150) {
        $error_code = 'name';
        return null;
    }

    if (!is_valid_material_type($material_type)) {
        $error_code = 'type';
        return null;
    }

    // Reject javascript:, data: and vbscript: at the trust boundary.
    // htmlspecialchars() would happily let those through.
    if (!is_valid_url($material_link) || strlen($material_link) > 255) {
        $error_code = 'link';
        return null;
    }

    if (strlen($description) > 255) {
        $error_code = 'desc';
        return null;
    }

    return array(
        'subject_id'    => $subject_id,
        'material_name' => $material_name,
        'material_type' => $material_type,
        'material_link' => safe_url($material_link),
        'description'   => $description,
    );
}


// ==================================================
// ADD MATERIAL
// ==================================================

if (isset($_POST['add_material'])) {

    if (!verify_csrf()) { die('Invalid request.'); }

    $error_code = "";
    $input = collect_material_input($conn, $user_id, $error_code);

    if ($input === null) {

        $message = $material_errors[$error_code] ?? $material_errors['save'];

    } else {

        $stmt = mysqli_prepare($conn,
            "INSERT INTO materials
                (user_id, subject_id, material_name,
                 material_type, material_link, description)
                VALUES (?, ?, ?, ?, ?, ?)"
        );

        $inserted = $stmt && mysqli_stmt_execute($stmt, array(
            $user_id,
            $input['subject_id'],
            $input['material_name'],
            $input['material_type'],
            $input['material_link'],
            $input['description'],
        ));

        if ($stmt) {
            mysqli_stmt_close($stmt);
        }

        // Only claim success when the row was really written. The old
        // code redirected with ?success=1 unconditionally.
        if ($inserted) {
            header("Location: materials.php?success=1");
            exit();
        }

        $message = $material_errors['save'];
    }
}


// ==================================================
// DELETE MATERIAL
// ==================================================

if (isset($_POST['delete_id'])) {

    if (!verify_csrf()) { die('Invalid request.'); }

    $id = (int) $_POST['delete_id'];

    $stmt = mysqli_prepare($conn,
        "DELETE FROM materials
            WHERE id = ?
            AND user_id = ?"
    );

    $deleted = false;

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            $deleted = mysqli_stmt_affected_rows($stmt) > 0;
        }

        mysqli_stmt_close($stmt);
    }

    header("Location: materials.php?" . ($deleted ? "deleted=1" : "err=save"));
    exit();
}


// ==================================================
// EDIT MATERIAL
// ==================================================

$edit_material = null;


if (isset($_GET['edit'])) {

    $id = (int) $_GET['edit'];

    $stmt = mysqli_prepare($conn,
        "SELECT *
            FROM materials
            WHERE id = ?
            AND user_id = ?"
    );

    $edit_result = null;

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
        mysqli_stmt_execute($stmt);
        $edit_result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    }

    // Guarded: mysqli_num_rows() is a TypeError on PHP 8 if the
    // statement failed and $result is false.
    if ($edit_result instanceof mysqli_result && mysqli_num_rows($edit_result) > 0) {

        $edit_material = mysqli_fetch_assoc($edit_result);

    }

}


// ==================================================
// UPDATE MATERIAL
// ==================================================

if (isset($_POST['update_material'])) {

    if (!verify_csrf()) { die('Invalid request.'); }

    $id = (int) $_POST['id'];

    $error_code = "";
    $input = collect_material_input($conn, $user_id, $error_code);

    if ($input === null) {

        // Bounce back through the edit form so the visitor keeps
        // context instead of landing on a bare error page.
        header("Location: materials.php?edit=$id&err=" . urlencode($error_code ?: 'save'));
        exit();

    }

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

    $updated = $stmt && mysqli_stmt_execute($stmt, array(
        $input['subject_id'],
        $input['material_name'],
        $input['material_type'],
        $input['material_link'],
        $input['description'],
        $id,
        $user_id,
    ));

    if ($stmt) {
        mysqli_stmt_close($stmt);
    }

    header("Location: materials.php?" . ($updated ? "updated=1" : "err=save"));
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
            href="logout.php?token=<?php echo e($csrf_token); ?>"
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


    <?php if ($message != "") { ?>

        <div class="material-message delete-material">

            <?php echo e($message); ?>

        </div>

    <?php } ?>


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
                     value="<?php echo e($csrf_token); ?>">


            <?php if ($edit_material != null) { ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo (int) $edit_material['id']; ?>"
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
                            value="<?php echo (int) $subject['id']; ?>"

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
                                    href="<?php echo e(safe_url($row['material_link'])); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="material-open-button"
                                >

                                    Open

                                </a>



                                <!-- EDIT -->

                                <a
                                    href="materials.php?edit=<?php echo (int) $row['id']; ?>"
                                    class="material-edit-button"
                                >

                                    Edit

                                </a>



                                <!-- DELETE -->

                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this material?');"><input type="hidden" name="delete_id" value="<?php echo (int) $row['id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>"><button type="submit" class="material-delete-button" style="background:none;border:none;padding:0;cursor:pointer;color:#df5353;font-weight:bold;font-size:12px;">Delete</button></form>


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
