<?php
session_start();

require_once "../db_connect.php";

/* =========================================================
   FETCH EVENTS
========================================================= */

$sql = "
    SELECT
        e.event_id,
        e.event_name,
        e.sport_id,
        e.description,
        e.event_date,
        e.registration_start,
        e.registration_end,
        e.location,
        e.status,
        e.created_at,
        s.sport_name
    FROM events e
    LEFT JOIN sports s
        ON e.sport_id = s.sport_id
    ORDER BY e.event_date ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("EVENT QUERY ERROR: " . mysqli_error($conn));
}


/* =========================================================
   FETCH SPORTS FOR FILTER
========================================================= */

$sports_sql = "
    SELECT sport_id, sport_name
    FROM sports
    ORDER BY sport_name ASC
";

$sports_result = mysqli_query(
    $conn,
    $sports_sql
);

if (!$sports_result) {
    die("SPORT QUERY ERROR: " . mysqli_error($conn));
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

    <title>Events Management | NexArena</title>

    <link
        rel="stylesheet"
        href="assets/sidebar.css"
    >

    <link
        rel="stylesheet"
        href="assets/event.css"
    >

</head>


<body>


<?php include "sidebar.php"; ?>


<main class="main-content">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="page-header">

        <div>

            <span class="page-label">
                EVENT MANAGEMENT
            </span>

            <h1>
                Events
            </h1>

            <p>
                Create and manage NexArena sports events.
            </p>

        </div>


        <a
            href="add_event.php"
            class="add-event-btn"
        >
            + Add Event
        </a>

    </div>


    <!-- =====================================================
         SUCCESS / ERROR MESSAGES
    ====================================================== -->

    <?php if (isset($_GET['success'])): ?>

        <div class="alert success">

            <?php

            if ($_GET['success'] === 'event_added') {

                echo "Event added successfully.";

            } elseif ($_GET['success'] === 'event_updated') {

                echo "Event updated successfully.";

            } elseif ($_GET['success'] === 'event_deleted') {

                echo "Event deleted successfully.";

            }

            ?>

        </div>

    <?php endif; ?>


    <?php if (isset($_GET['error'])): ?>

        <div class="alert error">

            <?php

            if ($_GET['error'] === 'event_not_found') {

                echo "Event not found.";

            } elseif ($_GET['error'] === 'event_in_use') {

                echo "This event cannot be deleted because registrations or teams are linked to it.";

            } elseif ($_GET['error'] === 'delete_failed') {

                echo "Unable to delete the event.";

            } else {

                echo "Something went wrong.";

            }

            ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         FILTER BAR
    ====================================================== -->

    <div class="filter-bar">


        <!-- SEARCH -->

        <div class="search-box">

            <span>⌕</span>

            <input
                type="text"
                id="eventSearch"
                placeholder="Search events..."
            >

        </div>


        <!-- SPORT -->

        <select id="sportFilter">

            <option value="all">
                All Sports
            </option>


            <?php while ($sport = mysqli_fetch_assoc($sports_result)): ?>

                <option
                    value="<?= $sport['sport_id'] ?>"
                >

                    <?= htmlspecialchars(
                        $sport['sport_name']
                    ) ?>

                </option>

            <?php endwhile; ?>

        </select>


        <!-- STATUS -->

        <select id="statusFilter">

            <option value="all">
                All Status
            </option>

            <option value="upcoming">
                Upcoming
            </option>

            <option value="ongoing">
                Ongoing
            </option>

            <option value="completed">
                Completed
            </option>

            <option value="cancelled">
                Cancelled
            </option>

        </select>

    </div>


    <!-- =====================================================
         EVENTS
    ====================================================== -->

    <div
        class="events-list"
        id="eventsList"
    >


        <?php if (mysqli_num_rows($result) > 0): ?>


            <?php while ($event = mysqli_fetch_assoc($result)): ?>


                <div
                    class="event-card"

                    data-name="<?= strtolower(
                        htmlspecialchars(
                            $event['event_name']
                        )
                    ) ?>"

                    data-sport="<?= $event['sport_id'] ?>"

                    data-status="<?= strtolower(
                        $event['status']
                    ) ?>"
                >


                    <!-- EVENT DATE -->

                    <div class="event-date-box">

                        <span>
                            <?= date(
                                "M",
                                strtotime(
                                    $event['event_date']
                                )
                            ) ?>
                        </span>

                        <strong>
                            <?= date(
                                "d",
                                strtotime(
                                    $event['event_date']
                                )
                            ) ?>
                        </strong>

                        <small>
                            <?= date(
                                "Y",
                                strtotime(
                                    $event['event_date']
                                )
                            ) ?>
                        </small>

                    </div>


                    <!-- EVENT DETAILS -->

                    <div class="event-details">

                        <div class="event-top">

                            <span class="sport-label">

                                <?= htmlspecialchars(
                                    $event['sport_name']
                                    ?? 'Unknown Sport'
                                ) ?>

                            </span>


                            <span
                                class="status-badge
                                <?= strtolower(
                                    $event['status']
                                ) ?>"
                            >

                                <?= ucfirst(
                                    $event['status']
                                ) ?>

                            </span>

                        </div>


                        <h2>

                            <?= htmlspecialchars(
                                $event['event_name']
                            ) ?>

                        </h2>


                        <p>

                            <?= !empty(
                                $event['event_description']
                            )

                                ? htmlspecialchars(
                                    $event['event_description']
                                )

                                : "No event description available."
                            ?>

                        </p>


                        <div class="event-meta">

                            <span>
                                📍
                                <?= htmlspecialchars(
                                    $event['location']
                                    ?? 'Not specified'
                                ) ?>
                            </span>

                            <span>
                                Registration:
                                <?= date(
                                    "d M Y",
                                    strtotime(
                                        $event['registration_start']
                                    )
                                ) ?>

                                -

                                <?= date(
                                    "d M Y",
                                    strtotime(
                                        $event['registration_end']
                                    )
                                ) ?>
                            </span>

                        </div>


                    </div>


                    <!-- ACTIONS -->

                    <div class="event-actions">

                        <a
                            href="edit_event.php?id=<?= $event['event_id'] ?>"
                            class="edit-btn"
                        >
                            Edit
                        </a>


                        <a
                            href="delete_event.php?id=<?= $event['event_id'] ?>"
                            class="delete-btn"

                            onclick="
                                return confirm(
                                    'Are you sure you want to delete this event?'
                                );
                            "
                        >
                            Delete
                        </a>

                    </div>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <div class="empty-state">

                <div>
                    📅
                </div>

                <h2>
                    No Events Found
                </h2>

                <p>
                    Create your first NexArena event.
                </p>

                <a href="add_event.php">
                    + Add Event
                </a>

            </div>


        <?php endif; ?>


    </div>


</main>


<!-- =====================================================
     FILTER SCRIPT
====================================================== -->

<script>

const searchInput =
    document.getElementById("eventSearch");

const sportFilter =
    document.getElementById("sportFilter");

const statusFilter =
    document.getElementById("statusFilter");

const eventCards =
    document.querySelectorAll(".event-card");


function filterEvents() {

    const search =
        searchInput.value
            .toLowerCase()
            .trim();

    const sport =
        sportFilter.value;

    const status =
        statusFilter.value
            .toLowerCase();


    eventCards.forEach(card => {

        const name =
            card.dataset.name;

        const cardSport =
            card.dataset.sport;

        const cardStatus =
            card.dataset.status;


        const matchesSearch =
            name.includes(search);


        const matchesSport =
            sport === "all" ||
            cardSport === sport;


        const matchesStatus =
            status === "all" ||
            cardStatus === status;


        if (
            matchesSearch &&
            matchesSport &&
            matchesStatus
        ) {

            card.style.display = "";

        } else {

            card.style.display = "none";

        }

    });

}


searchInput.addEventListener(
    "input",
    filterEvents
);

sportFilter.addEventListener(
    "change",
    filterEvents
);

statusFilter.addEventListener(
    "change",
    filterEvents
);

</script>


</body>
</html>