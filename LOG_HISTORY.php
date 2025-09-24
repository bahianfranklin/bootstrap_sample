<?php
    session_start();
    require 'db.php';  

    // Make sure user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: LOGIN.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];  // ✅ define it here

    // Get search keyword and date range from GET
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $from = isset($_GET['from']) ? trim($_GET['from']) : '';
    $to = isset($_GET['to']) ? trim($_GET['to']) : '';

    // Base SQL
    $sql = "SELECT l.id, u.name AS fullname, u.username, l.login_time, l.logout_time, l.ip_address
            FROM user_logs l
            JOIN users u ON l.user_id = u.id";

    // Prepare conditions
    $conditions = [];
    $params = [];
    $types = '';

    // ✅ Add user restriction first
    $conditions[] = "l.user_id = ?";
    $params[] = $user_id;
    $types .= 'i';

    // Add search condition
    if ($search !== '') {
        $conditions[] = "(u.name LIKE ? OR u.username LIKE ? OR l.ip_address LIKE ?)";
        $likeSearch = "%$search%";
        $params[] = $likeSearch;
        $params[] = $likeSearch;
        $params[] = $likeSearch;
        $types .= 'sss';
    }

    // Add date range conditions
    if ($from !== '') {
        $conditions[] = "l.login_time >= ?";
        $params[] = $from . " 00:00:00";
        $types .= 's';
    }
    if ($to !== '') {
        $conditions[] = "l.login_time <= ?";
        $params[] = $to . " 23:59:59";
        $types .= 's';
    }

    // Combine conditions
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY l.login_time DESC";

    // Prepare statement
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // --- PAGINATION SETUP ---
    // Get limit per page (default 10)
    $perPage = isset($_GET['limit']) ? $_GET['limit'] : 5;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

    // Clone SQL for counting total records
    $countSql = "SELECT COUNT(*) as total FROM user_logs l JOIN users u ON l.user_id = u.id";
    if (!empty($conditions)) {
        $countSql .= " WHERE " . implode(" AND ", $conditions);
    }

    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRecords = $countResult->fetch_assoc()['total'];

    // Compute total pages
    if ($perPage === "all") {
        $totalPages = 1;
        $offset = 0;
    } else {
        $perPage = intval($perPage);
        $totalPages = ceil($totalRecords / $perPage);
        $offset = ($page - 1) * $perPage;
    }

    // Final SQL with LIMIT
    $sql .= ($perPage === "all") ? "" : " LIMIT $offset, $perPage";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $userQuery = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $userQuery->bind_param("i", $user_id);
    $userQuery->execute();
    $user = $userQuery->get_result()->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Login History</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <script src="js/scripts.js"></script>

    </head> 

    <!-- Bootstrap & Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

    <!-- Custom Styles -->
    <link href="css/styles.css" rel="stylesheet" />

    <!-- Sidebar Toggle CSS -->
    <style>
        #layoutSidenav {
            display: flex;
        }
        #layoutSidenav_nav {
            width: 250px;
            flex-shrink: 0;
            transition: margin-left 0.3s ease;
        }
        #layoutSidenav_content {
            flex-grow: 1;
            min-width: 0;
            transition: margin-left 0.3s ease;
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
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseUsersInfo" aria-expanded="false" aria-controls="collapseUsersInfo">
                                <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                Users Info
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseUsersInfo" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="#">User Management</a>
                                    <a class="nav-link" href="#">Schedules</a>
                                    <a class="nav-link" href="#">Leave Credit</a>
                                    <a class="nav-link" href="#">Approvers Maintance</a>
                                </nav>
                            </div>
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
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseMaintenace" aria-expanded="false" aria-controls="collapseMaintenace">
                                <div class="sb-nav-link-icon"><i class="fas fa-toolbox"></i></div>
                                Maintenance
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseMaintenace" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="">Branch</a>
                                    <a class="nav-link" href="">Department</a>
                                    <a class="nav-link" href="">Position</a>
                                    <a class="nav-link" href="">Level</a>
                                    <a class="nav-link" href="">Tax Category</a>
                                    <a class="nav-link" href="">Status</a>
                                    <a class="nav-link" href="">Payroll Period</a>
                                    <a class="nav-link" href="">Footer Maintenance</a>
                                </nav>
                            </div>
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
                    <div class="container-fluid px-4">
                        <br>
                        <h2>Login / Logout History</h2>
                        <br>
                        <!-- Search form with From/To dates -->
                        <form method="get" class="mb-3 row g-2 align-items-end">
                            <div class="col-md-3">
                                <label><strong><em>Keyword</em></strong></label>
                                <input type="text" name="search" class="form-control" placeholder="Search by name, username, or IP" value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-3">
                                <label><strong><em>From:</em></strong></label>
                                <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>">
                            </div>
                            <div class="col-md-3">
                                <label><strong><em>To:</em></strong></label>
                                <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>">
                            </div>
                            <div class="col-md-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="?" class="btn btn-secondary">Reset</a>
                            </div>
                        </form>

                        <table class="table table-bordered table-striped mt-3">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>FULLNAME</th>
                                    <th>Username</th>
                                    <th>Login Time</th>
                                    <th>Logout Time</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                    <?php 
                                    $counter = 1; 
                                    while ($row = $result->fetch_assoc()): 
                                        $login_time = date("Y-m-d h:i:s A", strtotime($row['login_time']));
                                        $logout_time = $row['logout_time'] ? date("Y-m-d h:i:s A", strtotime($row['logout_time'])) : '---';
                                    ?>
                                    <tr>
                                        <td><?= $offset + $counter ?></td>
                                        <td><?= htmlspecialchars($row['fullname']) ?></td>
                                        <td><?= htmlspecialchars($row['username']) ?></td>
                                        <td><?= $login_time ?></td>
                                        <td><?= $logout_time ?></td>
                                        <td><?= htmlspecialchars($row['ip_address']) ?></td>
                                    </tr>
                                    <?php 
                                    $counter++;
                                    endwhile; 
                                    ?>
                            </tbody>
                        </table>
                        <br>
                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                            <!-- Pagination links -->
                            <nav>
                                <ul class="pagination mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page-1 ?>&limit=<?= $perPage ?>&search=<?= urlencode($search) ?>&from=<?= $from ?>&to=<?= $to ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i=1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= ($i==$page) ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&limit=<?= $perPage ?>&search=<?= urlencode($search) ?>&from=<?= $from ?>&to=<?= $to ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page+1 ?>&limit=<?= $perPage ?>&search=<?= urlencode($search) ?>&from=<?= $from ?>&to=<?= $to ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>

                            <!-- Page Dropdown -->
                            <form method="get" class="d-inline ms-3">
                                <input type="hidden" name="limit" value="<?= $perPage ?>">
                                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                                <input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">
                                <input type="hidden" name="to" value="<?= htmlspecialchars($to) ?>">

                                <label for="pageSelect">Page</label>
                                <select name="page" id="pageSelect" onchange="this.form.submit()">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <option value="<?= $i ?>" <?= ($i == $page) ? 'selected' : '' ?>>
                                            <?= "Page $i of $totalPages" ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </form>

                            <!-- Dropdown for limit -->
                            <form method="get" class="d-flex align-items-center ms-2">
                                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                                <input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">
                                <input type="hidden" name="to" value="<?= htmlspecialchars($to) ?>">
                                <input type="hidden" name="page" value="1">
                                <label class="me-2">Show</label>
                                <select name="limit" class="form-select w-auto" onchange="this.form.submit()">
                                    <option value="5" <?= ($perPage==5)?'selected':'' ?>>5</option>
                                    <option value="10" <?= ($perPage==10)?'selected':'' ?>>10</option>
                                    <option value="25" <?= ($perPage==25)?'selected':'' ?>>25</option>
                                    <option value="50" <?= ($perPage==50)?'selected':'' ?>>50</option>
                                    <option value="100" <?= ($perPage==100)?'selected':'' ?>>100</option>
                                    <option value="all" <?= ($perPage==='all')?'selected':'' ?>>Show All</option>
                                </select>
                                <label class="ms-2">entries</label>
                            </form>
                    

                            <!-- Export Dropdown -->
                            <form method="get" action="export_log_history.php" class="d-inline">
                                <label>Export:
                                    <select id="exportSelect" class="form-select d-inline-block w-auto" onchange="if(this.value) window.location.href=this.value;">
                                        <option value="">-- Select --</option>
                                        <option value="export_log_history.php?type=csv">CSV</option>
                                        <option value="export_log_history.php?type=excel">Excel</option>
                                        <option value="export_log_history.php?type=pdf">PDF</option>
                                    </select>
                                </label>
                            </form>
                        </div> 
                        <br>
                    </div>
                </main>
            </div>
        <!-- Bootstrap JS (includes Popper) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
