<?php
// ==============================
// Leave Requests Management
// ==============================

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

// ✅ Ensure logged in
if (!isset($_SESSION['user_id'])) {
    die("Please login first.");
}
$user_id = (int) $_SESSION['user_id'];

// ==============================
// Fetch Current User Info
// ==============================
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
if (!$user) {
    die("User not found.");
}

// ==============================
// Handle Add Leave Request
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $leave_type   = $_POST['leave_type'];
        $type         = $_POST['type'];
        $credit_value = (float) $_POST['credit_value'];
        $date_from    = $_POST['date_from'];
        $date_to      = $_POST['date_to'];
        $remarks      = $_POST['remarks'];

        // Validate date
        if (!DateTime::createFromFormat('Y-m-d', $date_from) || !DateTime::createFromFormat('Y-m-d', $date_to)) {
            die("Invalid date format.");
        }

        // Generate Application No
            $today = date("Ymd");
            // Count leave requests for this user today to make application_no unique per user per day
            $sql = "SELECT COUNT(*) as total FROM leave_requests WHERE user_id = $user_id AND DATE(date_applied) = CURDATE()";
            $res = $conn->query($sql);
            if (!$res) {
                die("Error counting leave_requests: " . $conn->error);
            }
            $row = $res->fetch_assoc();
            $countToday = $row['total'] + 1;
            $appNo = "L-" . $today . "-" . $user_id . "-" . str_pad($countToday, 2, "0", STR_PAD_LEFT);

        // Insert
        $stmt2 = $conn->prepare("
            INSERT INTO leave_requests 
            (application_no, user_id, leave_type, type, credit_value, date_from, date_to, remarks, date_applied) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt2->bind_param("sissdsss", $appNo, $user_id, $leave_type, $type, $credit_value, $date_from, $date_to, $remarks);

        if (!$stmt2->execute()) {
            die("Insert failed: " . $stmt2->error);
        }
        // ✅ Redirect after delete
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // ==============================
    // Handle Edit
    // ==============================
    if ($action === 'edit') {
        $id           = (int) $_POST['id'];
        $leave_type   = $_POST['leave_type'];
        $type         = $_POST['type'];
        $credit_value = (float) $_POST['credit_value'];
        $date_from    = $_POST['date_from'];
        $date_to      = $_POST['date_to'];
        $remarks      = $_POST['remarks'];
        $status       = $_POST['status'];

        if (!DateTime::createFromFormat('Y-m-d', $date_from) || !DateTime::createFromFormat('Y-m-d', $date_to)) {
            die("Invalid date format.");
        }

        $stmt3 = $conn->prepare("
            UPDATE leave_requests 
            SET leave_type=?, type=?, credit_value=?, date_from=?, date_to=?, remarks=?, status=?, date_updated=NOW() 
            WHERE id=? AND user_id=?
        ");
        $stmt3->bind_param("ssdssssii", $leave_type, $type, $credit_value, $date_from, $date_to, $remarks, $status, $id, $user_id);

        if (!$stmt3->execute()) {
            die("Update failed: " . $stmt3->error);
        }
        // ✅ Redirect after delete
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // ==============================
    // Handle Delete
    // ==============================
    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt4 = $conn->prepare("DELETE FROM leave_requests WHERE id=? AND user_id=?");
        $stmt4->bind_param("ii", $id, $user_id);
        if (!$stmt4->execute()) {
            die("Delete failed: " . $stmt4->error);
        }
        // ✅ Redirect after delete
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// ==============================
// Build Filtering Logic
// ==============================
$where = ["lr.user_id = $user_id"];

if (!empty($_GET['date_range'])) {
    $dates = explode(" to ", $_GET['date_range']);
    if (count($dates) === 2 && !empty($dates[0]) && !empty($dates[1])) {
        $from = $conn->real_escape_string($dates[0]);
        $to   = $conn->real_escape_string($dates[1]);
        $where[] = "lr.date_from >= '$from' AND lr.date_to <= '$to'";
    }
}

if (!empty($_GET['leave_type'])) {
    $leave_type = $conn->real_escape_string($_GET['leave_type']);
    $where[] = "lr.leave_type = '$leave_type'";
}

if (!empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $where[] = "lr.status = '$status'";
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// ==============================
// Fetch Leave Requests
// ==============================
$sql = "
    SELECT lr.*, u.username 
    FROM leave_requests lr 
    JOIN users u ON lr.user_id = u.id
    $whereSQL
    ORDER BY lr.date_applied DESC
";
$result = $conn->query($sql);
if (!$result) {
    die("Query Failed: " . $conn->error . " -- SQL: " . $sql);
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
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <title>Leave Requests Management</title>
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
                                <?php if ($approver): ?>
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
                                        <a class="nav-link" href="APPROVER_WORK_RESTDAY.PHP">Work On Restday</a>
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

        <div id="layoutSidenav_content">
            <main class="container-fluid px-4">
                <br>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="mb-0">Leave Requests</h2>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fa-solid fa-plus"></i> Apply Leave
                    </button>          
                </div>
                <div class="card mb-3 p-3">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Date Coverage</label>
                            <input type="text" class="form-control dateRangePicker" name="date_range" value="<?= htmlspecialchars($_GET['date_range'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Leave Type</label>
                            <select class="form-select" name="leave_type">
                                <option value="">Select</option>
                                <option value="Vacation Leave" <?= isset($_GET['leave_type']) && $_GET['leave_type'] == "Vacation Leave" ? "selected" : "" ?>>Vacation Leave</option>
                                <option value="Sick Leave" <?= isset($_GET['leave_type']) && $_GET['leave_type'] == "Sick Leave" ? "selected" : "" ?>>Sick Leave</option>
                                <option value="Mandatory Leave" <?= isset($_GET['leave_type']) && $_GET['leave_type'] == "Madatory Leave" ? "selected" : "" ?>>Mandatory Leave</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">Select</option>
                                <option value="Pending" <?= isset($_GET['status']) && $_GET['status'] == "Pending" ? "selected" : "" ?>>Pending</option>
                                <option value="Approved" <?= isset($_GET['status']) && $_GET['status'] == "Approved" ? "selected" : "" ?>>Approved</option>
                                <option value="Rejected" <?= isset($_GET['status']) && $_GET['status'] == "Rejected" ? "selected" : "" ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100">Filter</button>
                        </div>
                    </form>
                </div>

                <table class="table table-bordered table-hover bg-white">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Application No</th>
                            <th>User</th>
                            <th>Leave Type</th>
                            <th>Type</th>
                            <th>Credit</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Remarks</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['application_no']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['leave_type']) ?></td>
                            <td><?= htmlspecialchars($row['type']) ?></td>
                            <td><?= htmlspecialchars($row['credit_value']) ?></td>
                            <td><?= htmlspecialchars($row['date_from']) ?></td>
                            <td><?= htmlspecialchars($row['date_to']) ?></td>
                            <td><?= htmlspecialchars($row['remarks']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td>
                                <?php if ($row['status'] === 'Pending'): ?>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <?php else: ?>
                                    <span class="text-muted">WITH UPDATED STATUS</span>
                                <?php endif; ?>
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
                                            <h5 class="modal-title">Edit Leave Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-2">
                                                <label>Leave Type</label>
                                                <select class="form-select" name="leave_type" required>
                                                    <option value="Vacation Leave" <?= $row['leave_type'] == "Vacation Leave" ? "selected" : "" ?>>Vacation Leave</option>
                                                    <option value="Sick Leave" <?= $row['leave_type'] == "Sick Leave" ? "selected" : "" ?>>Sick Leave</option>
                                                    <option value="Mandatory Leave" <?= $row['leave_type'] == "Mandatory Leave" ? "selected" : "" ?>>Mandatory Leave</option>
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <label>Type</label>
                                                <select class="form-select" name="type" required>
                                                    <option value="With Pay" <?= $row['type'] == "With Pay" ? "selected" : "" ?>>With Pay</option>
                                                    <option value="Without Pay" <?= $row['type'] == "Without Pay" ? "selected" : "" ?>>Without Pay</option>
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <label>Credit Value</label>
                                                <input type="number" step="0.5" class="form-control" name="credit_value" value="<?= htmlspecialchars($row['credit_value']) ?>" required>
                                            </div>
                                            <div class="mb-2">
                                                <label>Date From</label>
                                                <input type="text" class="form-control datepicker" name="date_from" value="<?= htmlspecialchars($row['date_from']) ?>" required>
                                            </div>
                                            <div class="mb-2">
                                                <label>Date To</label>
                                                <input type="text" class="form-control datepicker" name="date_to" value="<?= htmlspecialchars($row['date_to']) ?>" required>
                                            </div>
                                            <div class="mb-2">
                                                <label>Remarks</label>
                                                <textarea class="form-control" name="remarks" required><?= htmlspecialchars($row['remarks']) ?></textarea>
                                            </div>
                                            <div class="mb-2">
                                                <label>Status</label>
                                                <select class="form-select" name="status" required>
                                                    <option value="Pending" <?= $row['status'] == "Pending" ? "selected" : "" ?>>Pending</option>
                                                    <option value="Approved" <?= $row['status'] == "Approved" ? "selected" : "" ?>>Approved</option>
                                                    <option value="Rejected" <?= $row['status'] == "Rejected" ? "selected" : "" ?>>Rejected</option>
                                                </select>
                                            </div>
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
                                            <h5 class="modal-title">Cancel Leave Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            Are you sure you want to cancel <strong><?= htmlspecialchars($row['application_no']) ?></strong>?
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-danger">Proceed</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <?php endwhile; ?>
                    </tbody>
                </table>
            </main>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Apply Leave Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label>Leave Type</label>
                            <select class="form-select" name="leave_type" required>
                                <option value="">-- Select --</option>
                                <option value="Vacation Leave">Vacation Leave</option>
                                <option value="Sick Leave">Sick Leave</option>
                                <option value="Mandatory Leave">Mandatory Leave</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label>Type</label>
                            <select class="form-select" name="type" required>
                                <option value="">-- Select --</option>
                                <option value="With Pay">With Pay</option>
                                <option value="Without Pay">Without Pay</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label>Credit Value</label>
                            <input type="number" step="0.5" class="form-control" name="credit_value" required>
                        </div>
                        <div class="mb-2">
                            <label>Date From</label>
                            <input type="text" class="form-control datepicker" name="date_from" required>
                        </div>
                        <div class="mb-2">
                            <label>Date To</label>
                            <input type="text" class="form-control datepicker" name="date_to" required>
                        </div>
                        <div class="mb-2">
                            <label>Remarks</label>
                            <textarea class="form-control" name="remarks" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Add</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // ✅ Range picker for filters
        flatpickr(".dateRangePicker", {
            mode: "range",
            dateFormat: "Y-m-d"
        });

        // ✅ Single date pickers for leave application (From / To)
        flatpickr(".datepicker", {
            dateFormat: "Y-m-d",
            allowInput: true
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
