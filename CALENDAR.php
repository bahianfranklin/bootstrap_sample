<?php

    session_start();
    require 'db.php';

    $success = "";
    $error = "";

    // ✅ make sure user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: LOGIN.php");
        exit();
    }
    $user_id = $_SESSION['user_id'];

    // ✅ Fetch logged-in user info
    $userQuery = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $userQuery->bind_param("i", $user_id);
    $userQuery->execute();
    $user = $userQuery->get_result()->fetch_assoc();

    // ✅ ADD EVENT
    if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['update_event']) && !isset($_POST['delete_event']) && (!isset($_POST['action']) || $_POST['action'] !== 'delete')) {
        $event_type  = $_POST['event_type'];
        $title       = trim($_POST['title']);
        $date        = $_POST['date'];
        $location    = trim($_POST['location']);
        $description = trim($_POST['description']);
        $visibility  = $_POST['visibility'];

        $stmt = $conn->prepare("INSERT INTO holidays (event_type, title, date, location, description, visibility) 
                                VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssss", $event_type, $title, $date, $location, $description, $visibility);
            if ($stmt->execute()) {
                $success = "✅ Event added successfully!";
                header("Location: calendar1.php?success=1");
                exit();
            } else {
                $error = "❌ Insert failed: " . $stmt->error;
                header("Location: calendar1.php?success=1");
                exit();
            }
            $stmt->close();
        } else {
            $error = "❌ SQL Prepare failed: " . $conn->error;
            header("Location: calendar1.php?success=1");
            exit();
        }
    }

    // ✅ UPDATE EVENT
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_event'])) {
        $id          = intval($_POST['id']);
        $event_type  = $_POST['event_type'];
        $title       = trim($_POST['title']);
        $date        = $_POST['date'];
        $location    = trim($_POST['location']);
        $description = trim($_POST['description']);
        $visibility  = $_POST['visibility'];

        $stmt = $conn->prepare("UPDATE holidays 
                                SET event_type=?, title=?, date=?, location=?, description=?, visibility=? 
                                WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("ssssssi", $event_type, $title, $date, $location, $description, $visibility, $id);
            if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $success = "✅ Event updated successfully!";
                header("Location: calendar1.php?success=1");
                exit();
            } else {
                $error = "⚠️ No changes made. Either the ID does not exist or the data is the same.";
                header("Location: calendar1.php?success=1");
                exit();
            }
            } else {
                $error = "❌ Update failed: " . $stmt->error;
                header("Location: calendar1.php?success=1");
                exit();
            }
        }
    }

    // ✅ DELETE EVENT
    if ($_SERVER["REQUEST_METHOD"] === "POST" && (isset($_POST['delete_event']) || (isset($_POST['action']) && $_POST['action'] === 'delete'))) {
        $id = intval($_POST['id']);

        $stmt = $conn->prepare("DELETE FROM holidays WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                if (isset($_POST['action'])) {
                    echo json_encode(["status" => "success"]);
                    exit;
                }
                $success = "🗑️ Event deleted successfully!";
            } else {
                if (isset($_POST['action'])) {
                    echo json_encode(["status" => "error", "message" => $stmt->error]);
                    exit;
                }
                $error = "❌ Delete failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "❌ SQL Prepare failed: " . $conn->error;
        }
    }

     function isApprover($conn, $user_id) {
        $sql = "SELECT 1 FROM approver_assignments WHERE user_id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    $approver = isApprover($conn, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Calendar</title>
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    </head>

     <!-- Bootstrap & Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

    <!-- Custom Styles -->
    <link href="css/styles.css" rel="stylesheet" />

    <style>
        /* Sidebar & Layout */
        #layoutSidenav {
            display: flex;
            min-height: 100vh;
        }

        #layoutSidenav_nav {
            width: 250px;
            flex-shrink: 0;
            transition: margin-left 0.3s ease;
        }

        #layoutSidenav_content {
            flex-grow: 1;
            min-width: 0;
            transition: margin 0.3s ease;
            padding: 20px;
            box-sizing: border-box;
        }

        /* Calendar container */
        #calendar {
            font-family: Arial, sans-serif;
            width: 100%;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 10px rgba(0,0,0,.08);
        }

        /* Legend */
        #calendar-legend {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-right: 20px;
            padding: 15px;
            font-size: 14px;
            flex: 0 0 200px;
        }

        /* Event styling */
        .fc-event,
        .fc-event-title {
            font-size: 11px;
            line-height: 1.2;
        }

        /* Responsive Fix */
        @media (max-width: 992px) {
            #calendar-legend {
                display: none;   /* hide legend on small screens */
            }
            #layoutSidenav_nav {
                width: 200px;
            }
        }
    </style>
	
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
            <!-- Navbar Brand-->
            <a class="navbar-brand ps-3" href="INDEX.php">Sample System Logo</a>
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
            <!-- Navbar-->
            <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0"></form>
                <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" 
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user fa-fw"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">

                            <!-- User Profile Section -->
                            <li class="px-2 py-2 text-center">
                                <?php 
                                    $profilePath = "uploads/" . ($user['profile_pic'] ?? '');
                                    if (!empty($user['profile_pic']) && file_exists($profilePath)): ?>
                                        <img src="<?= $profilePath ?>?t=<?= time(); ?>" 
                                            alt="Profile Picture" 
                                            class="img-thumbnail rounded-circle mb-2" 
                                            style="width:80px; height: 80px; object-fit:cover;">
                                    <?php else: ?>
                                        <img src="uploads/default.png" 
                                            class="rounded-circle border border-2 mb-2" 
                                            style="width:80px; height:80px; object-fit:cover;">
                                    <?php endif; ?>
                                <p class="fw-bold mb-1" style="font-size: 14px;"><strong><?= htmlspecialchars($user['name'] ?? 'N/A'); ?></strong></p>
                                <p class="text-muted mb-1" style="font-size: 12px">Username: <?= htmlspecialchars($user['username'] ?? 'N/A'); ?></p>
                                <p class="text-muted mb-1" style="font-size: 12px">Role: <?= htmlspecialchars($user['role'] ?? 'N/A'); ?></p>
                                <p class="text-muted mb-0" style="font-size: 12px;">Status: <?= htmlspecialchars($user['status'] ?? 'N/A'); ?></p>
                            </li>

                            <li><hr class="dropdown-divider" /></li>
                            <li><a class="dropdown-item" href="#!">Settings</a></li>
                            <li><a class="dropdown-item" href="LOCK.PHP">Lock Screen</a></li>
                            <li><hr class="dropdown-divider" /></li>
                            <li><a class="dropdown-item" href="LOGOUT.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
        </nav>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <a class="nav-link" href="index.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Dashboard
                            </a>
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseApplication" aria-expanded="false" aria-controls="collapseApplication">
                                <div class="sb-nav-link-icon"><i class="fas fa-file"></i></div>
                                Application
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseApplication" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="LEAVE_APPLICATION.PHP">Leave Application</a>
                                    <a class="nav-link" href="OVERTIME.PHP">Overtime</a>
                                    <a class="nav-link" href="OFFICIAL_BUSINESS.PHP">Official Business</a>
                                    <a class="nav-link" href="CHANGE_SCHEDULE.PHP">Change Schedule</a>
                                    <a class="nav-link" href="FAILURE_CLOCK.PHP">Failure to Clock In/Out</a>
                                    <a class="nav-link" href="CLOCK_ALTERATION.PHP">Clock Alteration</a>
                                    <a class="nav-link" href="WORK_RESTDAY">Work On Restday</a>
                                </nav>
                            </div>
                            <?php if ($approver): ?>
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseApproving" aria-expanded="false" aria-controls="collapseApproving">
                                <div class="sb-nav-link-icon"><i class="fas fa-check-circle"></i></div>
                                Approving
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseApproving" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="LEAVE_APPLICATION.PHP">Leave Application</a>
                                    <a class="nav-link" href="OVERTIME.PHP">Overtime</a>
                                    <a class="nav-link" href="OFFICIAL_BUSINESS.PHP">Official Business</a>
                                    <a class="nav-link" href="CHANGE_SCHEDULE.PHP">Change Schedule</a>
                                    <a class="nav-link" href="FAILURE_CLOCK.PHP">Failure to Clock In/Out</a>
                                    <a class="nav-link" href="CLOCK_ALTERATION.PHP">Clock Alteration</a>
                                    <a class="nav-link" href="WORK_RESTDAY">Work On Restday</a>
                                </nav>
                            </div>
                            <?php endif; ?>
                            <a class="nav-link" href="USER_MAINTENANCE.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-building"></i></div>
                                Users Info
                            </a>
                            <a class="nav-link" href="DIRECTORY.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-building"></i></div>
                                Directory
                            </a>
                            <a class="nav-link" href="CONTACT_DETAILS.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-phone"></i></div>
                                Contact Details
                            </a>
                            <a class="nav-link" href="CALENDAR.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-calendar"></i></div>
                                Calendar
                            </a>
                            <a class="nav-link" href="LOG_HISTORY.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-clipboard-list"></i></div>
                                Log History 
                            </a>
                            <a class="nav-link" href="MAINTENANCE.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-toolbox"></i></div>
                                Maintenance
                            </a>
                        </div>
                    </div>
                    <div class="sb-sidenav-footer">
                        <div class="small">Logged in as:</div>
                        Start Bootstrap
                    </div>
                </nav>
            </div>
                <br>
        <div id="layoutSidenav_content">
            <main>
                <div class="container mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="mb-0">Company Calendar</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">➕ Add Event</button>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <!-- ✅ Add Event Modal -->
                <div class="modal fade" id="addEventModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="calendar1.php">
                                <div class="modal-header">
                                    <h5 class="modal-title">Add New Event</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <label class="form-label">Event Type</label>
                                    <select name="event_type" class="form-select" required>
                                        <option value="Birthday">Birthday</option>
                                        <option value="Holiday">Holiday</option>
                                        <option value="Custom">Custom</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <label class="form-label mt-2">Title</label>
                                    <input type="text" name="title" class="form-control" required>
                                    <label class="form-label mt-2">Date</label>
                                    <input type="date" name="date" class="form-control" required>
                                    <label class="form-label mt-2">Location</label>
                                    <input type="text" name="location" class="form-control">
                                    <label class="form-label mt-2">Description</label>
                                    <textarea name="description" class="form-control"></textarea>
                                    <label class="form-label mt-2">Visibility</label>
                                    <select name="visibility" class="form-select">
                                        <option value="Public">Public</option>
                                        <option value="Private">Private</option>
                                    </select>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-success">Save Event</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            <div style="display: flex;">
                <!-- Legend -->
                <div id="calendar-legend" style="width: 200px; padding: 10px; font-family: Arial; font-size: 14px;">
                    <h3 style="margin-bottom: 10px; margin-top: 150px">Legend</h3>
                    <div style="margin-bottom: 8px;">
                    <span style="display:inline-block; width:16px; height:16px; background:#f39c12; margin-right:6px; border-radius:3px;"></span>
                    Birthday 🎂
                    </div>
                    <div style="margin-bottom: 8px;">
                    <span style="display:inline-block; width:16px; height:16px; background:#28a745; margin-right:6px; border-radius:3px;"></span>
                    Holiday 🎉
                    </div>
                    <div style="margin-bottom: 8px;">
                    <span style="display:inline-block; width:16px; height:16px; background:#007bff; margin-right:6px; border-radius:3px;"></span>
                    Schedule Leaves 📌
                    </div>
                    <div style="margin-bottom: 8px;">
                    <span style="display:inline-block; width:16px; height:16px; background:#6c757d; margin-right:6px; border-radius:3px;"></span>
                    Other 📅
                    </div>
            </div>

            <!-- Calendar -->
            <div id="calendar" style="flex-grow: 1;"></div>
            </div>

            <!-- ✅ Edit Event Modal -->
            <div class="modal fade" id="editEventModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="calendar1.php">
                            <input type="hidden" name="update_event" value="1">
                            <input type="hidden" name="id" id="edit-id">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Event</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <label class="form-label">Event Type</label>
                                <select name="event_type" id="edit-event_type" class="form-select" required></select>
                                <label class="form-label mt-2">Title</label>
                                <input type="text" name="title" id="edit-title" class="form-control" required>
                                <label class="form-label mt-2">Date</label>
                                <input type="date" name="date" id="edit-date" class="form-control" required>
                                <label class="form-label mt-2">Location</label>
                                <input type="text" name="location" id="edit-location" class="form-control">
                                <label class="form-label mt-2">Description</label>
                                <textarea name="description" id="edit-description" class="form-control"></textarea>
                                <label class="form-label mt-2">Visibility</label>
                                <select name="visibility" id="edit-visibility" class="form-select"></select>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success">Update Event</button>
                                <button type="button" class="btn btn-danger" id="deleteEventBtn">Delete</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            </main>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/rrule@2.6.4/dist/es5/rrule.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/rrule@6.1.11/index.global.min.js"></script>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                initialView: 'dayGridMonth',
                events: 'FETCH-BIRTHDAYS.php',
                eventClick: function(info) {
                    const event = info.event;
                    document.getElementById('edit-id').value = event.id; // ✅ not extendedProps.id
                    document.getElementById('edit-title').value = event.title;
                    document.getElementById('edit-date').value = event.startStr;
                    document.getElementById('edit-location').value = event.extendedProps.location || '';
                    document.getElementById('edit-description').value = event.extendedProps.description || '';

                    document.getElementById('edit-event_type').innerHTML = `
                        <option ${event.extendedProps.event_type=='Birthday'?'selected':''}>Birthday</option>
                        <option ${event.extendedProps.event_type=='Holiday'?'selected':''}>Holiday</option>
                        <option ${event.extendedProps.event_type=='Custom'?'selected':''}>Custom</option>
                        <option ${event.extendedProps.event_type=='Other'?'selected':''}>Other</option>
                    `;
                    document.getElementById('edit-visibility').innerHTML = `
                        <option ${event.extendedProps.visibility=='Public'?'selected':''}>Public</option>
                        <option ${event.extendedProps.visibility=='Private'?'selected':''}>Private</option>
                    `;
                    new bootstrap.Modal(document.getElementById('editEventModal')).show();
                }
            });
            calendar.render();

            // ✅ delete function
            document.getElementById('deleteEventBtn').addEventListener('click', function () {
                if (!confirm("Are you sure you want to delete this event?")) return;

                const eventId = document.getElementById('edit-id').value;

                fetch('calendar1.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'delete',
                        id: eventId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        alert("Event deleted!");
                        const modalEl = document.getElementById('editEventModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        modal.hide();
                        calendar.refetchEvents();
                    } else {
                        alert("Delete failed: " + data.message);
                    }
                })
                .catch(err => console.error(err));
            });
        });
        </script>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const body = document.body;
                const sidebarToggle = document.querySelector("#sidebarToggle");

                if (sidebarToggle) {
                    sidebarToggle.addEventListener("click", function (e) {
                        e.preventDefault();
                        body.classList.toggle("sb-sidenav-toggled");
                    });
                }
            });
        </script>
    </body>
</html>
