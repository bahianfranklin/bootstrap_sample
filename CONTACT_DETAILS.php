<?php
    session_start(); // ✅ Required to access $_SESSION
    require 'CONFIG.php';

    //Make sure user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: LOGIN.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];  // ✅ define it here

    // Fetch current user info for profile display
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $user = $userResult->fetch_assoc();

    // Get search keyword and date range from GET
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $from = isset($_GET['from']) ? trim($_GET['from']) : '';
    $to = isset($_GET['to']) ? trim($_GET['to']) : '';

    // ✅ Get search keyword
    $search = $_GET['search'] ?? '';

    // ✅ Get per-page limit from dropdown (default 5)
    $perPage = $_GET['limit'] ?? 5;

    // ✅ Current page
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

    // ✅ Fetch all records from API
    $url = $baseUrl . "?action=view";
    $result = requestData($url);
    $result = preg_replace('/^[^\{]+/', '', $result);
    $result = preg_replace('/[^\}]+$/', '', $result);
    $dataArray = json_decode($result, true);

    $records = $dataArray['data'] ?? [];

    // ✅ Filter if search entered
    if (!empty($search)) {
        $records = array_filter($records, function($row) use ($search) {
            return stripos($row['fullname'], $search) !== false ||
                stripos($row['address'], $search) !== false ||
                stripos($row['contact_no'], $search) !== false;
        });
    }

    // ✅ Pagination setup
    $totalRecords = count($records);

    if ($perPage === "all") {
        // Show ALL records
        $currentRecords = $records;
        $totalPages = 1;
        $page = 1;
        $offset = 0;
    } else {
        $perPage = intval($perPage);
        $totalPages = max(1, ceil($totalRecords / $perPage));
        $offset = ($page - 1) * $perPage;
        $currentRecords = array_slice($records, $offset, $perPage);
    }

    // ✅ Handle Add, Edit, Delete in same file
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($_POST['action'] === 'add') {
            $fullname = $_POST['fullname'];
            $address = $_POST['address'];
            $contact_no = $_POST['contact_no'];

            $url = $baseUrl . "?action=add";
            requestData($url, "POST", [
                "fullname" => $fullname,
                "address" => $address,
                "contact_no" => $contact_no
            ]);

            header("Location: contact_details.php?success=1");
            exit;
        }

    if ($_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $fullname = $_POST['fullname'];
        $address = $_POST['address'];
        $contact_no = $_POST['contact_no'];

        $url = $baseUrl . "?action=update";
        $response = requestData($url, "POST", [
            "record_id" => $id,   // <-- FIX HERE
            "fullname"  => $fullname,
            "address"   => $address,
            "contact_no"=> $contact_no
        ]);

        $respData = json_decode($response, true);
        if (isset($respData['error'])) {
            die("Update failed: " . htmlspecialchars($respData['error']));
        }

        header("Location: contact_details.php?updated=1");
        exit;
    }

        if ($_POST['action'] === 'delete') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                $url = $baseUrl . "?action=delete";
                $data = ["record_id" => $id];
                requestData($url, "POST", $data);
            }
            header("Location: contact_details.php?delete=1");
            exit;
        }
    }

    // ✅ Fetch all contacts for display
    $url = $baseUrl . "?action=list";
    $contacts = requestData($url);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <title>Contacts Details</title>
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
                                        <a class="nav-link" href="APPROVER_WORK_RESTDAY.PHP">Work On Restday</a>
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
            <main>
                <div class="container mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="m-0">Contacts Details</h3>

                    <!-- Add Modal Trigger -->
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fa fa-plus"></i> Add New Employee
                    </button>
                </div>

                <!-- Search Form -->
                <form method="get" class="mb-3 d-flex">
                    <input type="text" name="search" class="form-control me-2" 
                        placeholder="Search by name, address and contact..."
                        value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="contact_details.php" class="btn btn-secondary ms-2">Reset</a>
                </form>

                <?php if (!empty($currentRecords)): ?>
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Contact No</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody> 
                        <?php foreach ($currentRecords as $i => $row): ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td><?= htmlspecialchars($row['fullname']) ?></td>
                                <td><?= htmlspecialchars($row['address']) ?></td>
                                <td><?= htmlspecialchars($row['contact_no']) ?></td>
                                <td>
                                    <!-- View Modal Trigger -->
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['id'] ?>">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <!-- Edit Modal Trigger -->
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">
                                        <i class="fa fa-pen"></i>
                                    </button>
                                    <!-- Delete Modal Trigger -->
                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- View Modal -->
                            <div class="modal fade" id="viewModal<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                    <div class="modal-header bg-info text-white">
                                        <h5 class="modal-title">View Contact</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><b>Name:</b> <?= htmlspecialchars($row['fullname']) ?></p>
                                        <p><b>Address:</b> <?= htmlspecialchars($row['address']) ?></p>
                                        <p><b>Contact:</b> <?= htmlspecialchars($row['contact_no']) ?></p>
                                    </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $row['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post" action="">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">

                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title" id="editModalLabel<?= $row['id'] ?>">Edit Contact</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label for="fullname">Full Name</label>
                                                    <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($row['fullname']) ?>" class="form-control" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="address">Address</label>
                                                    <input type="text" id="address" name="address" value="<?= htmlspecialchars($row['address']) ?>" class="form-control" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="contact_no">Contact No</label>
                                                    <input type="text" id="contact_no" name="contact_no" value="<?= htmlspecialchars($row['contact_no']) ?>" class="form-control" required>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Update</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        
                            <!-- Delete Modal -->
                            <div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                <form method="post" action="">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title">Confirm Delete</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                    Are you sure you want to delete <b><?= htmlspecialchars($row['fullname']) ?></b>?
                                    </div>
                                    <div class="modal-footer">
                                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                    </div>
                                </form>
                                </div>
                            </div>
                            </div>

                            <?php endforeach; ?>
                            </tbody>
                        </table>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap"> 
                    <!-- ✅ Pagination controls-->
                    <nav>
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" 
                                    href="?page=<?= $page-1 ?>&limit=<?= $perPage ?>&search=<?= urlencode($search) ?>">
                                    Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                    <a class="page-link" 
                                    href="?page=<?= $i ?>&limit=<?= $perPage ?>&search=<?= urlencode($search) ?>">
                                    <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" 
                                    href="?page=<?= $page+1 ?>&limit=<?= $perPage ?>&search=<?= urlencode($search) ?>">
                                    Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>

                    <!-- ✅ Page Dropdown Pagination -->
                    <form method="get" class="d-inline">
                        <input type="hidden" name="limit" value="<?= $perPage ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <label for="pageSelect">Page</label>
                        <select name="page" id="pageSelect" onchange="this.form.submit()">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <option value="<?= $i ?>" <?= ($i == $page) ? 'selected' : '' ?>>
                                    Page <?= $i ?> of <?= $totalPages ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </form>

                    <!-- ✅ Dropdown entries -->
                    <form method="get" action="contact_details.php" class="d-inline">
                        <input type="hidden" name="page" value="<?= $page ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <label>Show 
                            <select name="limit" onchange="this.form.submit()">
                                <option value="5" <?= ($perPage == 5) ? 'selected' : '' ?>>5</option>
                                <option value="10" <?= ($perPage == 10) ? 'selected' : '' ?>>10</option>
                                <option value="25" <?= ($perPage == 25) ? 'selected' : '' ?>>25</option>
                                <option value="50" <?= ($perPage == 50) ? 'selected' : '' ?>>50</option>
                                <option value="100" <?= ($perPage == 100) ? 'selected' : '' ?>>100</option>
                                <option value="all" <?= ($perPage === 'all') ? 'selected' : '' ?>>Show All</option>
                            </select>
                            entries
                        </label>
                    </form>

                    <!-- ✅ Export file -->
                    <form method="get" action="Export.php" class="d-inline">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <input type="hidden" name="page" value="<?= $page ?>">
                        <input type="hidden" name="limit" value="<?= $perPage ?>">
                        <label>Export:
                            <select name="type" onchange="this.form.submit()" class="form-select d-inline w-auto">
                                <option value="">-- Select --</option>
                                <option value="csv">CSV</option>
                                <option value="excel">Excel</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </label>
                    </form>               
            </div>
        <?php else: ?>
            <p>No records found.</p>
        <?php endif; ?>

        <!-- ✅ Add Modal -->
        <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
            <form method="post" action="">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Add New Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                <div class="mb-3">
                    <label>Full Name</label>
                    <input type="text" name="fullname" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Address</label>
                    <input type="text" name="address" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Contact No</label>
                    <input type="text" name="contact_no" class="form-control" required>
                </div>
                </div>
                <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save</button>
                </div>
            </form>
            </div>
        </div>
        </main>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>   
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
