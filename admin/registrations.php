<?php

session_start();

/* =========================================================
   ADMIN ACCESS
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (
    !isset($_SESSION['role']) ||
    ($_SESSION['role'] !== 'admin' &&
     $_SESSION['role'] !== 'super_admin')
) {
    header("Location: ../login.php");
    exit();
}


/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once __DIR__ . '/../db_connect.php';


/* =========================================================
   SEARCH & FILTER
========================================================= */

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$status = isset($_GET['status'])
    ? trim($_GET['status'])
    : '';


/* =========================================================
   STATISTICS
========================================================= */

$totalRegistrations = 0;
$pendingRegistrations = 0;
$approvedRegistrations = 0;
$rejectedRegistrations = 0;


/* Total */

$sqlTotal = "
    SELECT COUNT(*) AS total
    FROM event_registrations
";

$resultTotal = mysqli_query($conn, $sqlTotal);

if ($resultTotal) {
    $row = mysqli_fetch_assoc($resultTotal);
    $totalRegistrations = $row['total'];
}


/* Pending */

$sqlPending = "
    SELECT COUNT(*) AS total
    FROM event_registrations
    WHERE status = 'pending'
";

$resultPending = mysqli_query($conn, $sqlPending);

if ($resultPending) {
    $row = mysqli_fetch_assoc($resultPending);
    $pendingRegistrations = $row['total'];
}


/* Approved */

$sqlApproved = "
    SELECT COUNT(*) AS total
    FROM event_registrations
    WHERE status = 'approved'
";

$resultApproved = mysqli_query($conn, $sqlApproved);

if ($resultApproved) {
    $row = mysqli_fetch_assoc($resultApproved);
    $approvedRegistrations = $row['total'];
}


/* Rejected */

$sqlRejected = "
    SELECT COUNT(*) AS total
    FROM event_registrations
    WHERE status = 'rejected'
";

$resultRejected = mysqli_query($conn, $sqlRejected);

if ($resultRejected) {
    $row = mysqli_fetch_assoc($resultRejected);
    $rejectedRegistrations = $row['total'];
}


/* =========================================================
   REGISTRATION DATA
========================================================= */

$sql = "
    SELECT
        er.registration_id,
        er.status,
        er.registered_at,

        u.user_id,
        u.full_name,
        u.username,
        u.email,

        e.event_id,
        e.event_name,
        e.event_date,

        s.sport_name

    FROM event_registrations er

    INNER JOIN users u
        ON er.user_id = u.user_id

    INNER JOIN events e
        ON er.event_id = e.event_id

    LEFT JOIN sports s
        ON e.sport_id = s.sport_id

    WHERE 1=1
";


/* Search */

if ($search !== '') {

    $safeSearch = mysqli_real_escape_string(
        $conn,
        $search
    );

    $sql .= "
        AND (
            u.full_name LIKE '%$safeSearch%'
            OR u.username LIKE '%$safeSearch%'
            OR u.email LIKE '%$safeSearch%'
            OR e.event_name LIKE '%$safeSearch%'
            OR s.sport_name LIKE '%$safeSearch%'
        )
    ";
}


/* Status */

if (
    $status === 'pending' ||
    $status === 'approved' ||
    $status === 'rejected'
) {

    $safeStatus = mysqli_real_escape_string(
        $conn,
        $status
    );

    $sql .= "
        AND er.status = '$safeStatus'
    ";
}


/* Latest first */

$sql .= "
    ORDER BY er.registered_at DESC
";


$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>NexArena - Registration Management</title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

    <link
        rel="stylesheet"
        href="assets/registrations.css"
    >

</head>


<body>


<!-- =====================================================
     YOUR ADMIN SIDEBAR
===================================================== -->

<?php include 'sidebar.php'; ?>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="admin-main">


    <!-- =================================================
         HEADER
    ================================================== -->

    <header class="admin-header">

        <div class="header-left">

            <button
                class="sidebar-toggle"
                id="sidebarToggle"
                type="button"
            >
                ☰
            </button>

            <h1>
                Registration Management
            </h1>

        </div>


        <div class="header-right">

            <div class="header-notification">
                🔔
            </div>

            <div class="header-admin-avatar">
                <?php
                echo strtoupper(
                    substr(
                        $_SESSION['username'] ?? 'A',
                        0,
                        1
                    )
                );
                ?>
            </div>

        </div>

    </header>


    <!-- =================================================
         CONTENT
    ================================================== -->

    <section class="admin-content">


        <!-- PAGE HEADING -->

        <div class="page-heading">

            <span class="page-label">
                NEXARENA ADMIN
            </span>

            <h2>
                Registrations
            </h2>

            <p>
                Manage event registrations and participant requests.
            </p>

        </div>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="registration-stats">


            <!-- TOTAL -->

            <div class="registration-stat">

                <div class="registration-stat-icon">
                    👥
                </div>

                <div>

                    <span>
                        Total Registrations
                    </span>

                    <strong>
                        <?php echo $totalRegistrations; ?>
                    </strong>

                </div>

            </div>


            <!-- PENDING -->

            <div class="registration-stat">

                <div class="registration-stat-icon">
                    ⏳
                </div>

                <div>

                    <span>
                        Pending
                    </span>

                    <strong>
                        <?php echo $pendingRegistrations; ?>
                    </strong>

                </div>

            </div>


            <!-- APPROVED -->

            <div class="registration-stat">

                <div class="registration-stat-icon">
                    ✓
                </div>

                <div>

                    <span>
                        Approved
                    </span>

                    <strong>
                        <?php echo $approvedRegistrations; ?>
                    </strong>

                </div>

            </div>


            <!-- REJECTED -->

            <div class="registration-stat">

                <div class="registration-stat-icon">
                    ✕
                </div>

                <div>

                    <span>
                        Rejected
                    </span>

                    <strong>
                        <?php echo $rejectedRegistrations; ?>
                    </strong>

                </div>

            </div>

        </div>


        <!-- =================================================
             SEARCH & FILTER
        ================================================== -->

        <div class="registration-toolbar">

            <form
                method="GET"
                class="registration-search"
            >


                <div class="search-box">

                    <span>
                        🔍
                    </span>

                    <input
                        type="text"
                        name="search"
                        placeholder="Search participant, email, event or sport..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>


                <select name="status">

                    <option value="">
                        All Status
                    </option>

                    <option
                        value="pending"
                        <?php
                        if ($status === 'pending') {
                            echo 'selected';
                        }
                        ?>
                    >
                        Pending
                    </option>

                    <option
                        value="approved"
                        <?php
                        if ($status === 'approved') {
                            echo 'selected';
                        }
                        ?>
                    >
                        Approved
                    </option>

                    <option
                        value="rejected"
                        <?php
                        if ($status === 'rejected') {
                            echo 'selected';
                        }
                        ?>
                    >
                        Rejected
                    </option>

                </select>


                <button
                    type="submit"
                    class="filter-btn"
                >
                    Filter
                </button>


                <a
                    href="registrations.php"
                    class="clear-btn"
                >
                    Clear
                </a>

            </form>

        </div>


        <!-- =================================================
             REGISTRATION TABLE
        ================================================== -->

        <div class="registration-card">


            <div class="registration-card-header">

                <h3>
                    Event Registrations
                </h3>

                <p>
                    Review and manage registered participants.
                </p>

            </div>


            <div class="table-wrapper">

                <table class="registration-table">

                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                PARTICIPANT
                            </th>

                            <th>
                                EMAIL
                            </th>

                            <th>
                                EVENT
                            </th>

                            <th>
                                SPORT
                            </th>

                            <th>
                                EVENT DATE
                            </th>

                            <th>
                                REGISTERED
                            </th>

                            <th>
                                STATUS
                            </th>

                            <th>
                                ACTION
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php

                    if ($result && mysqli_num_rows($result) > 0):

                        while ($row = mysqli_fetch_assoc($result)):

                            $fullname =
                                $row['full_name']
                                ?: $row['username'];

                            $initial =
                                strtoupper(
                                    substr(
                                        $fullname,
                                        0,
                                        1
                                    )
                                );

                    ?>

                        <tr>


                            <!-- ID -->

                            <td>

                                #<?php
                                echo $row['registration_id'];
                                ?>

                            </td>


                            <!-- PARTICIPANT -->

                            <td>

                                <div class="participant">

                                    <div class="participant-avatar">

                                        <?php
                                        echo htmlspecialchars(
                                            $initial
                                        );
                                        ?>

                                    </div>

                                    <div>

                                        <strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $fullname
                                            );
                                            ?>

                                        </strong>

                                        <span>

                                            @<?php
                                            echo htmlspecialchars(
                                                $row['username']
                                            );
                                            ?>

                                        </span>

                                    </div>

                                </div>

                            </td>


                            <!-- EMAIL -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $row['email']
                                );
                                ?>

                            </td>


                            <!-- EVENT -->

                            <td>

                                <span class="event-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $row['event_name']
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- SPORT -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $row['sport_name']
                                    ?? 'N/A'
                                );

                                ?>

                            </td>


                            <!-- EVENT DATE -->

                            <td>

                                <?php

                                if (!empty($row['event_date'])) {

                                    echo date(
                                        'd M Y',
                                        strtotime(
                                            $row['event_date']
                                        )
                                    );

                                } else {

                                    echo 'N/A';

                                }

                                ?>

                            </td>


                            <!-- REGISTERED DATE -->

                            <td>

                                <?php

                                echo date(
                                    'd M Y',
                                    strtotime(
                                        $row['registered_at']
                                    )
                                );

                                ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <?php

                                $currentStatus =
                                    strtolower(
                                        $row['status']
                                    );

                                if (
                                    $currentStatus === 'approved'
                                ) {

                                    echo '
                                    <span class="status-badge status-approved">
                                        Approved
                                    </span>';

                                } elseif (
                                    $currentStatus === 'rejected'
                                ) {

                                    echo '
                                    <span class="status-badge status-rejected">
                                        Rejected
                                    </span>';

                                } else {

                                    echo '
                                    <span class="status-badge status-pending">
                                        Pending
                                    </span>';

                                }

                                ?>

                            </td>


                            <!-- ACTION -->

                            <td>

                                <div class="registration-actions">


                                <?php if ($currentStatus !== 'approved'): ?>

                                    <form
                                        action="registration_action.php"
                                        method="POST"
                                    >

                                        <input
                                            type="hidden"
                                            name="registration_id"
                                            value="<?php
                                            echo $row['registration_id'];
                                            ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="approve"
                                        >

                                        <button
                                            type="submit"
                                            class="approve-btn"
                                        >
                                            Approve
                                        </button>

                                    </form>

                                <?php endif; ?>


                                <?php if ($currentStatus !== 'rejected'): ?>

                                    <form
                                        action="registration_action.php"
                                        method="POST"
                                    >

                                        <input
                                            type="hidden"
                                            name="registration_id"
                                            value="<?php
                                            echo $row['registration_id'];
                                            ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="reject"
                                        >

                                        <button
                                            type="submit"
                                            class="reject-btn"
                                        >
                                            Reject
                                        </button>

                                    </form>

                                <?php endif; ?>


                                </div>

                            </td>


                        </tr>


                    <?php

                        endwhile;

                    else:

                    ?>

                        <tr>

                            <td
                                colspan="9"
                                class="no-registrations"
                            >

                                <div class="empty-icon">
                                    📋
                                </div>

                                <h3>
                                    No registrations found
                                </h3>

                                <p>
                                    There are currently no registrations matching your search.
                                </p>

                            </td>

                        </tr>

                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </div>


    </section>

</main>


<!-- =====================================================
     SIDEBAR MOBILE SCRIPT
===================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const toggle =
            document.getElementById(
                "sidebarToggle"
            );

        const sidebar =
            document.querySelector(
                ".admin-sidebar"
            );


        if (toggle && sidebar) {

            toggle.addEventListener(
                "click",
                function () {

                    sidebar.classList.toggle(
                        "mobile-open"
                    );

                }
            );

        }

    }
);

</script>


</body>

</html>