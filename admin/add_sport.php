<?php
session_start();

require_once "../db_connect.php";
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
    ===================================================== */

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
           CHECK DUPLICATE SPORT
        ================================================= */

        $check_sql = "
            SELECT sport_id
            FROM sports
            WHERE LOWER(sport_name) = LOWER(?)
            LIMIT 1
        ";

        $check_stmt = mysqli_prepare($conn, $check_sql);

        if ($check_stmt) {

            mysqli_stmt_bind_param(
                $check_stmt,
                "s",
                $sport_name
            );

            mysqli_stmt_execute($check_stmt);

            mysqli_stmt_store_result($check_stmt);


            if (mysqli_stmt_num_rows($check_stmt) > 0) {

                $message = "This sport already exists.";
                $message_type = "error";

            } else {


                /* =========================================
                   INSERT SPORT
                ========================================= */

                $insert_sql = "
                    INSERT INTO sports
                    (
                        sport_name,
                        category,
                        description,
                        status
                    )
                    VALUES (?, ?, ?, ?)
                ";

                $insert_stmt =
                    mysqli_prepare($conn, $insert_sql);


                if ($insert_stmt) {

                    mysqli_stmt_bind_param(
                        $insert_stmt,
                        "ssss",
                        $sport_name,
                        $category,
                        $description,
                        $status
                    );


                    if (mysqli_stmt_execute($insert_stmt)) {

                        header(
                            "Location: sports.php?success=sport_added"
                        );

                        exit();

                    } else {

                        $message =
                            "Unable to add sport: "
                            . mysqli_stmt_error($insert_stmt);

                        $message_type = "error";

                    }

                    mysqli_stmt_close($insert_stmt);

                } else {

                    $message =
                        "Database error: "
                        . mysqli_error($conn);

                    $message_type = "error";

                }

            }

            mysqli_stmt_close($check_stmt);

        } else {

            $message =
                "Database error: "
                . mysqli_error($conn);

            $message_type = "error";

        }

    }

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

    <title>Add Sport | NexArena</title>

    <link
        rel="stylesheet"
        href="assets/sidebar.css"
    >

    <link
        rel="stylesheet"
        href="assets/add-sports.css"
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
                    Add Sport
                </h1>

                <p>
                    Add a new sport to the NexArena platform.
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
                    class="form-message <?= $message_type ?>"
                >

                    <?= htmlspecialchars($message) ?>

                </div>

            <?php endif; ?>


            <!-- FORM -->

            <form
                method="POST"
                action=""
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
                        placeholder="Enter sport name"
                        value="<?= htmlspecialchars($_POST["sport_name"] ?? "") ?>"
                        maxlength="100"
                        required
                    >

                    <small>
                        Example: Football, Cricket, Kabaddi, Esports
                    </small>

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
                            <?= (($_POST["category"] ?? "") === "Team Sport") ? "selected" : "" ?>
                        >
                            Team Sport
                        </option>

                        <option
                            value="Individual Sport"
                            <?= (($_POST["category"] ?? "") === "Individual Sport") ? "selected" : "" ?>
                        >
                            Individual Sport
                        </option>

                        <option
                            value="Indoor"
                            <?= (($_POST["category"] ?? "") === "Indoor") ? "selected" : "" ?>
                        >
                            Indoor
                        </option>

                        <option
                            value="Outdoor"
                            <?= (($_POST["category"] ?? "") === "Outdoor") ? "selected" : "" ?>
                        >
                            Outdoor
                        </option>

                        <option
                            value="Esports"
                            <?= (($_POST["category"] ?? "") === "Esports") ? "selected" : "" ?>
                        >
                            Esports
                        </option>

                        <option
                            value="Other"
                            <?= (($_POST["category"] ?? "") === "Other") ? "selected" : "" ?>
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
                        placeholder="Enter a short description about this sport..."
                    ><?= htmlspecialchars($_POST["description"] ?? "") ?></textarea>

                    <small>
                        Maximum 500 characters.
                    </small>

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
                            <?= (($_POST["status"] ?? "active") === "active") ? "selected" : "" ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?= (($_POST["status"] ?? "") === "inactive") ? "selected" : "" ?>
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
                        + Add Sport
                    </button>

                </div>


            </form>

        </div>


    </main>


</body>

</html>