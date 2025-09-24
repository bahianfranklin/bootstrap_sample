<?php 
    ob_start();                // ✅ Start output buffering
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $activeTab = $_GET['tab'] ?? 'branch'; // default to branch

    if (!isset($_SESSION['user'])) {
        header("Location: LOGIN.php");
        exit();
    }

    $user = $_SESSION['user'];

    // ✅ Assume logged-in user
    $user_id = $_SESSION['user_id'] ?? 1; // change if needed
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Dashboard - SAMPLE SYSTEM</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
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
                    <div class="container-fluid px-4">
                        </br>
                        <h3 class="m-0">Maintenance Tabs</h3>
                        <br>
                        <ul class="nav nav-tabs" id="maintenanceTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link <?= $activeTab=='branch' ? 'active' : '' ?>" data-bs-toggle="tab" href="#branch">Branch</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $activeTab=='department' ? 'active' : '' ?>" data-bs-toggle="tab" href="#department">Departments</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $activeTab=='position' ? 'active' : '' ?>" data-bs-toggle="tab" href="#position">Position</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $activeTab=='level' ? 'active' : '' ?>" data-bs-toggle="tab" href="#level">Level</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $activeTab=='tax' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tax">Tax Category</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $activeTab=='status' ? 'active' : '' ?>" data-bs-toggle="tab" href="#status">Status</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $activeTab=='payroll' ? 'active' : '' ?>" data-bs-toggle="tab" href="#payroll">Payroll Period</a>
                            </li>
                        </ul>

                        <div class="tab-content mt-3">
                            <div class="tab-pane fade <?= $activeTab=='branch' ? 'show active' : '' ?>" id="branch">
                                <?php include 'branch.php'; ?>
                            </div>
                            <div class="tab-pane fade <?= $activeTab=='department' ? 'show active' : '' ?>" id="department">
                                <?php include 'department.php'; ?>
                            </div>
                            <div class="tab-pane fade <?= $activeTab=='position' ? 'show active' : '' ?>" id="position">
                                <?php include 'position.php'; ?>
                            </div>
                            <div class="tab-pane fade <?= $activeTab=='level' ? 'show active' : '' ?>" id="level">
                                <?php include 'level.php'; ?>
                            </div>
                            <div class="tab-pane fade <?= $activeTab=='tax' ? 'show active' : '' ?>" id="tax">
                                <?php include 'tax.php'; ?>
                            </div>
                            <div class="tab-pane fade <?= $activeTab=='status' ? 'show active' : '' ?>" id="status">
                                <?php include 'status.php'; ?>
                            </div>
                            <div class="tab-pane fade <?= $activeTab=='payroll' ? 'show active' : '' ?>" id="payroll">
                                <?php include 'payroll_periods.php'; ?>
                            </div>
                        </div>
                    </div>
                </main>
            </div>

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
