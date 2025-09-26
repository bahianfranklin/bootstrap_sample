<?php
    session_start();
    require 'db.php';

    if (!isset($_SESSION['user_id'])) {
        die("Please login first.");
    }

    $user_id = $_SESSION['user_id'];

    // Fetch current user info for profile display
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $user = $userResult->fetch_assoc();

   
    /** ========== ADD ========= */
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $ot_date      = $_POST['date'];
        $from_time    = $_POST['from_time'];
        $to_time      = $_POST['to_time'];
        $purpose      = $_POST['purpose'];

        // ✅ Radio button → single string (no array)
        $work_schedule= $_POST['work_schedule'] ?? "";

        $today = date("Ymd");
        $res = $conn->query("SELECT COUNT(*) as total FROM overtime WHERE DATE(datetime_applied)=CURDATE()");
        $row = $res->fetch_assoc();
        $countToday = $row['total'] + 1;
        $appNo = "OT-" . $today . "-" . str_pad($countToday, 2, "0", STR_PAD_LEFT);

        $stmt = $conn->prepare("INSERT INTO overtime 
            (application_no, date, from_time, to_time, purpose, work_schedule, applied_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", $appNo, $ot_date, $from_time, $to_time, $purpose, $work_schedule, $user_id);
        $stmt->execute();
    }

    /** ========== EDIT ========= */
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id           = $_POST['id'];
        $ot_date      = $_POST['date'];
        $from_time    = $_POST['from_time'];
        $to_time      = $_POST['to_time'];
        $purpose      = $_POST['purpose'];
        $work_schedule= $_POST['work_schedule'] ?? "";
        $status       = $_POST['status'];

        $stmt = $conn->prepare("UPDATE overtime 
            SET date=?, from_time=?, to_time=?, purpose=?, work_schedule=?, status=?, datetime_updated=NOW() 
            WHERE id=?");
        $stmt->bind_param("ssssssi", $ot_date, $from_time, $to_time, $purpose, $work_schedule, $status, $id);
        $stmt->execute();
    }

    /** ========== DELETE ========= */
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM overtime WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    /** ========== FETCH ========= */
    $where = ["ot.applied_by = $user_id"];
    $params = [];
    $types  = "";

    // 📅 Date range filter
    if (!empty($_GET['date_range'])) {
        $range = explode(" to ", $_GET['date_range']);
        if (count($range) == 2) {
            $from = $range[0];
            $to   = $range[1];
            $where[] = "ot.date BETWEEN ? AND ?";
            $params[] = $from;
            $params[] = $to;
            $types   .= "ss";
        }
    }

    // ✅ Status filter
    if (!empty($_GET['status'])) {
        $where[] = "ot.status = ?";
        $params[] = $_GET['status'];
        $types   .= "s";
    }

    // ✅ Build final SQL
    $sql = "SELECT ot.*, u.username 
            FROM overtime ot 
            JOIN users u ON ot.applied_by = u.id";

    // Add WHERE if needed
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY ot.datetime_applied DESC";

    // ✅ Run query
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Error: " . $conn->error); // 👈 show exact error
    }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

?>

<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <title>Overtime Requests</title>
        <link href="css/styles.css" rel="stylesheet" />
        <!-- include Flatpickr CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <!-- FontAwesome 6.4.0 CDN for icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
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
                            <li><a class="dropdown-item" href="EDIT_USER_PROFILE.php?id=<?= $user['id'] ?>">Edit Profile</a></li>
                            <li><a class="dropdown-item" href="#!">Settings</a></li>
                            <li><a class="dropdown-item" href="#!">Activity Log</a></li>
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
                                <a class="nav-link" href="INDEX.php">
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
                                        <a class="nav-link" href="WORK_RESTDAY.PHP">Work On Restday</a>
                                    </nav>
                                </div>
                                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseApproving" aria-expanded="false" aria-controls="collapseApproving">
                                    <div class="sb-nav-link-icon"><i class="fas fa-check-circle"></i></div>
                                    Approving
                                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                                </a>
                                <div class="collapse" id="collapseApproving" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                                    <nav class="sb-sidenav-menu-nested nav">
                                        <a class="nav-link" href="PENDING_LEAVES.PHP">Leave Application</a>
                                        <a class="nav-link" href="APPROVER_OVERTIME.PHP">Overtime</a>
                                        <a class="nav-link" href="APPROVER_OFFICIAL_BUSINESS.PHP">Official Business</a>
                                        <a class="nav-link" href="APPROVER_CHANGE_SCHEDULE.PHP">Change Schedule</a>
                                        <a class="nav-link" href="APPROVER_FAILURE_CLOCK.PHP">Failure to Clock In/Out</a>
                                        <a class="nav-link" href="APPROVER_CLOCK_ALTERATION.PHP">Clock Alteration</a>
                                        <a class="nav-link" href="APPROVER_WORK_RESTDAY">Work On Restday</a>
                                    </nav>
                                </div>
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

        <div id="layoutSidenav_content">
            <main class="container-fluid px-4">
            <br>
            <div class="d-flex justify-content-between mb-3">
                <h3>Overtime Requests</h3>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">+ Apply Overtime</button>
            </div>

                <div class="card mb-3 p-3">
                    <form method="GET" class="row g-2">
                        <div class="col-md-4">
                            <input type="text" name="date_range" class="form-control dateRangePicker" 
                                placeholder="Select Date Range" value="<?= $_GET['date_range'] ?? '' ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="Pending" <?= ($_GET['status'] ?? '')=="Pending"?"selected":"" ?>>Pending</option>
                                <option value="Approved" <?= ($_GET['status'] ?? '')=="Approved"?"selected":"" ?>>Approved</option>
                                <option value="Rejected" <?= ($_GET['status'] ?? '')=="Rejected"?"selected":"" ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>

            <table class="table table-bordered table-hover bg-white">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Application No</th>
                        <th>User</th>
                        <th>Date</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Purpose</th>
                        <th>Work Schedule</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; while($row=$result->fetch_assoc()): ?>

                    <?php
                    // Status badge
                        $statusClass = "secondary";
                        if ($row['status'] == "Pending")  $statusClass = "warning";
                        if ($row['status'] == "Approved") $statusClass = "success";
                        if ($row['status'] == "Rejected") $statusClass = "danger";
                    ?>

                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= $row['application_no'] ?></td>
                        <td><?= $row['username'] ?></td>
                        <td><?= $row['date'] ?></td>
                        <td><?= date("h:i A", strtotime($row['from_time'])) ?></td>
                        <td><?= date("h:i A", strtotime($row['to_time'])) ?></td>
                        <td><?= $row['purpose'] ?></td>
                        <td><?= $row['work_schedule'] ?></td>
                        <td><span class="badge bg-<?= $statusClass ?>"><?= $row['status'] ?></span></td>
                        <td>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">Edit</button>
                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>">Delete</button>
                        </td>
                    </tr>
                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title">Edit Overtime Request</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <label>Date</label>
                                        <input type="date" name="date" value="<?= $row['date'] ?>" class="form-control mb-2" required>
                                        <label>From Time</label>
                                        <input type="time" name="from_time" value="<?= $row['from_time'] ?>" class="form-control mb-2" required>
                                        <label>To Time</label>
                                        <input type="time" name="to_time" value="<?= $row['to_time'] ?>" class="form-control mb-2" required>
                                        <label>Purpose</label>
                                        <textarea name="purpose" class="form-control mb-2"><?= $row['purpose'] ?></textarea>
                                        <?php $ws = explode(", ", $row['work_schedule']); ?>
                                        <label>Work Schedule</label><br>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="work_schedule" value="On-site"
                                                <?= $row['work_schedule']=="On-site" ? "checked" : "" ?>>
                                            <label class="form-check-label">On-site</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="work_schedule" value="Work-from-home"
                                                <?= $row['work_schedule']=="Work-from-home" ? "checked" : "" ?>>
                                            <label class="form-check-label">Work-from-home</label>
                                        </div>
                                        <label>Status</label>
                                        <select name="status" class="form-select">
                                            <option <?= $row['status']=="Pending"?"selected":"" ?>>Pending</option>
                                            <option <?= $row['status']=="Approved"?"selected":"" ?>>Approved</option>
                                            <option <?= $row['status']=="Rejected"?"selected":"" ?>>Rejected</option>
                                        </select>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Update</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Delete Modal -->
                    <div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <div class="modal-content">
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title">Delete Overtime Request</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        Are you sure to delete <strong><?= $row['application_no'] ?></strong>?
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Modal -->
        <div class="modal fade" id="addModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">Apply Overtime</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label>Date</label>
                            <input type="date" name="date" class="form-control mb-2" required>
                            <label>From Time</label>
                            <input type="time" name="from_time" class="form-control mb-2" required>
                            <label>To Time</label>
                            <input type="time" name="to_time" class="form-control mb-2" required>
                            <label>Purpose</label>
                            <textarea name="purpose" class="form-control mb-2" required></textarea>
                            <label>Work Schedule</label><br>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="work_schedule" value="On-site" id="wsOnsite" required>
                                    <label class="form-check-label" for="wsOnsite">On-site</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="work_schedule" value="Work-from-home" id="wsWFH" required>
                                    <label class="form-check-label" for="wsWFH">Work-from-home</label>
                                </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success">Submit</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </form>
            </div>
            </main>
        </div>

        <script>
                // For Date Range Picker
                flatpickr(".dateRangePicker", {
                    mode: "range",
                    dateFormat: "Y-m-d",
                    onClose: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length === 2) {
                            const from = instance.formatDate(selectedDates[0], "Y-m-d");
                            const to   = instance.formatDate(selectedDates[1], "Y-m-d");
                            instance.input.value = `${from} to ${to}`;
                        }
                    }
                });

                // For Time Picker (12-hour format with AM/PM)
                flatpickr(".timepicker", {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "h:i K",  // 12-hour format with AM/PM
                    time_24hr: false
                });
        </script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script>
            flatpickr(".dateRangePicker", {
                mode: "range",
                dateFormat: "Y-m-d",
                onChange: function (selectedDates, dateStr, instance) {
                    // optionally, if you want to fill hidden inputs or something
                }
            });
        </script>
    </body>
</html>
