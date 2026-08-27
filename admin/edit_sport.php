<?php
session_start();

require_once "../db_connect.php";

/* =========================================================
   GET SPORT ID
========================================================= */

$sport_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($sport_id <= 0) {
    header("Location: sports.php");
    exit();
}


/* =========================================================
   FETCH SPORT
========================================================= */

$select_sql = "
    SELECT
        sport_id,
        sport_name,
        category,
        description,
        status,
        created_at
    FROM sports
    WHERE sport_id = ?
    LIMIT 1
";

$select_stmt = mysqli_prepare($conn, $select_sql);

if (!$select_stmt) {
    die("DATABASE ERROR: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $select_stmt,
    "i",
    $sport_id
);

mysqli_stmt_execute($select_stmt);

$result = mysqli_stmt_get_result($select_stmt);

$sport = mysqli_fetch_assoc($result);

mysqli_stmt_close($select_stmt);


/* =========================================================
   SPORT NOT FOUND
========================================================= */

if (!$sport) {
    header("Location: sports.php?error=sport_not_found");
    exit();
}


/* =========================================================
   FORM PROCESSING
========================================================= */

$message = "";
$message_type = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $sport_name = trim($_POST["sport_name"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $status = $_POST["status"] ?? "active";


    /* =====================================================
       VALIDATION
    ====================================================== */

    if ($sport_name === "") {

        $message = "Sport name is required.";
        $message_type = "error";

    } elseif ($category === "") {

        $message = "Please select a category.";
        $message_type = "error";

    } elseif (!in_array($status, ["active", "inactive"], true)) {

        $message = "Invalid status.";
        $message_type = "error";

    } else {


        /* =================================================
           CHECK DUPLICATE SPORT NAME
        ================================================= */

        $check_sql = "
            SELECT sport_id
            FROM sports
            WHERE LOWER(sport_name) = LOWER(?)
            AND sport_id != ?
            LIMIT 1
        ";

        $check_stmt = mysqli_prepare(
            $conn,
            $check_sql
        );


        if (!$check_stmt) {

            $message =
                "Database error: " .
                mysqli_error($conn);

            $message_type = "error";

        } else {

            mysqli_stmt_bind_param(
                $check_stmt,
                "si",
                $sport_name,
                $sport_id
            );

            mysqli_stmt_execute($check_stmt);

            mysqli_stmt_store_result($check_stmt);


            /* =============================================
               DUPLICATE FOUND
            ============================================== */

            if (mysqli_stmt_num_rows($check_stmt) > 0) {

                $message =
                    "Another sport with this name already exists.";

                $message_type = "error";

            } else {


                /* =========================================
                   UPDATE SPORT
                ========================================== */

                $update_sql = "
                    UPDATE sports
                    SET
                        sport_name = ?,
                        category = ?,
                        description = ?,
                        status = ?
                    WHERE sport_id = ?
                ";

                $update_stmt = mysqli_prepare(
                    $conn,
                    $update_sql
                );


                if (!$update_stmt) {

                    $message =
                        "Database error: " .
                        mysqli_error($conn);

                    $message_type = "error";

                } else {

                    mysqli_stmt_bind_param(
                        $update_stmt,
                        "ssssi",
                        $sport_name,
                        $category,
                        $description,
                        $status,
                        $sport_id
                    );


                    if (mysqli_stmt_execute($update_stmt)) {

                        mysqli_stmt_close($update_stmt);
                        mysqli_stmt_close($check_stmt);

                        header(
                            "Location: sports.php?success=sport_updated"
                        );

                        exit();

                    } else {

                        $message =
                            "Unable to update sport: " .
                            mysqli_stmt_error($update_stmt);

                        $message_type = "error";

                    }

                    mysqli_stmt_close($update_stmt);
                }
            }

            mysqli_stmt_close($check_stmt);
        }
    }


    /* =====================================================
       KEEP ENTERED VALUES AFTER ERROR
    ====================================================== */

    $sport["sport_name"] = $sport_name;
    $sport["category"] = $category;
    $sport["description"] = $description;
    $sport["status"] = $status;

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

    <title>Edit Sport | NexArena</title>

    <link
        rel="stylesheet"
        href="assets/sidebar.css"
    >

    <link
        rel="stylesheet"
        href="assets/edit-sport.css"
    >

</head>


<body>


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <?php include "sidebar.php"; ?>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <!-- =================================================
             PAGE HEADER
        ================================================== -->

        <div class="page-header">

            <div>

                <span class="page-label">
                    SPORTS MANAGEMENT
                </span>

                <h1>
                    Edit Sport
                </h1>

                <p>
                    Update sport information and status.
                </p>

            </div>


            <a
                href="sports.php"
                class="back-btn"
            >
                ← Back to Sports
            </a>

        </div>


        <!-- =================================================
             FORM CARD
        ================================================== -->

        <div class="form-card">


            <!-- MESSAGE -->

            <?php if ($message !== ""): ?>

                <div
                    class="form-message <?= htmlspecialchars($message_type) ?>"
                >
                    <?= htmlspecialchars($message) ?>
                </div>

            <?php endif; ?>


            <!-- FORM -->

            <form
                method="POST"
                action="edit_sport.php?id=<?= $sport_id ?>"
                autocomplete="off"
            >


                <!-- =========================================
                     SPORT NAME
                ========================================== -->

                <div class="form-group">

                    <label for="sport_name">

                        Sport Name

                        <span>*</span>

                    </label>

                    <input
                        type="text"
                        id="sport_name"
                        name="sport_name"
                        value="<?= htmlspecialchars($sport['sport_name']) ?>"
                        placeholder="Enter sport name"
                        maxlength="100"
                        required
                    >

                </div>


                <!-- =========================================
                     CATEGORY
                ========================================== -->

                <div class="form-group">

                    <label for="category">

                        Category

                        <span>*</span>

                    </label>

                    <select
                        id="category"
                        name="category"
                        required
                    >

                        <option value="">
                            Select Category
                        </option>


                        <option
                            value="Team Sport"
                            <?= $sport['category'] === 'Team Sport' ? 'selected' : '' ?>
                        >
                            Team Sport
                        </option>


                        <option
                            value="Individual Sport"
                            <?= $sport['category'] === 'Individual Sport' ? 'selected' : '' ?>
                        >
                            Individual Sport
                        </option>


                        <option
                            value="Indoor"
                            <?= $sport['category'] === 'Indoor' ? 'selected' : '' ?>
                        >
                            Indoor
                        </option>


                        <option
                            value="Outdoor"
                            <?= $sport['category'] === 'Outdoor' ? 'selected' : '' ?>
                        >
                            Outdoor
                        </option>


                        <option
                            value="Esports"
                            <?= $sport['category'] === 'Esports' ? 'selected' : '' ?>
                        >
                            Esports
                        </option>


                        <option
                            value="Other"
                            <?= $sport['category'] === 'Other' ? 'selected' : '' ?>
                        >
                            Other
                        </option>

                    </select>

                </div>


                <!-- =========================================
                     DESCRIPTION
                ========================================== -->

                <div class="form-group">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                        maxlength="500"
                        placeholder="Enter sport description..."
                    ><?= htmlspecialchars($sport['description'] ?? '') ?></textarea>

                </div>


                <!-- =========================================
                     STATUS
                ========================================== -->

                <div class="form-group">

                    <label for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                    >

                        <option
                            value="active"
                            <?= $sport['status'] === 'active' ? 'selected' : '' ?>
                        >
                            Active
                        </option>


                        <option
                            value="inactive"
                            <?= $sport['status'] === 'inactive' ? 'selected' : '' ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>


                <!-- =========================================
                     ACTIONS
                ========================================== -->

                <div class="form-actions">

                    <a
                        href="sports.php"
                        class="cancel-btn"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        class="save-btn"
                    >
                        Save Changes
                    </button>

                </div>


            </form>

        </div>


    </main>


</body>

</html>