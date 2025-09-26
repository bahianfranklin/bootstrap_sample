<?php
    session_start();
    require 'db.php';

    // 🚫 Prevent cached pages
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: 0");

    if (!isset($_SESSION['user'])) {
        header("Location: LOGIN.php");
        exit();
    }

    $user = $_SESSION['user'];

    // ✅ Assume logged-in user
    $user_id = $_SESSION['user_id'] ?? 1; // change if needed

    // ✅ Get Leave Balance
    $leave_sql = "SELECT mandatory, vacation_leave, sick_leave FROM leave_credits WHERE user_id = ?";
    $stmt = $conn->prepare($leave_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $leave = $stmt->get_result()->fetch_assoc();

    // ✅ Get Events
    $today = date("Y-m-d");
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
                <main>
                    <div class="container-fluid px-4">
                        <h2 class="mt-4">Employee Dashboard</h2>
                        <!-- Row for Welcome + Date -->
                        <div class="container mt-5">
                            <div class="card p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h3 class="mb-0">
                                        Welcome, <?= htmlspecialchars($user['name']); ?> 👋
                                    </h3>
                                    <h5 class="text-muted mb-0"><?= date('l, F j, Y'); ?></h5>
                                </div>
                            </div>
                        </div>

                        <div class="container">
                            <h1 class="mb-4"></h1>

                            <!-- Leave Balance -->
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">Leave Balance</div>
                                    <div class="card-body">
                                        <div id="leaveBalance">
                                            Loading leave balance...
                                        </div>
                                    </div>
                            </div>

                            <!-- Events -->
                            <div class="card mb-3">
                                <div class="card-header bg-success text-white">Events</div>
                                    <div class="card-body">
                                        <div id="eventSection">
                                            Loading events...
                                        </div>
                                    </div>
                            </div>
                            
                            <!-- Work Schedule -->
                            <div class="card mb-3">
                                <div class="card-header bg-warning">Work Schedule</div>
                                    <div id="scheduleSection">
                                        Loading schedule...
                                    </div>
                            </div>

                            <!-- Payroll Period -->
                            <div class="card mb-3">
                                <div class="card-header bg-info">Current Payroll Period</div>
                                    <div class="card-body">
                                        <div id="payrollSection">
                                            Loading payroll...
                                        </div>
                                    </div>
                            </div>
                        
                            <div class="card mb-3">
                                <div class="card-header bg-danger">Pending</div>
                                    <div class="card-body">
                                        <li><a class="dropdown-item" href="pending_leaves.php">
                                            <i class="fa fa-plane"></i> Leave 
                                            <span class="badge bg-danger" id="pendingLeaves">0</span>
                                        </a></li>

                                        <li><a class="dropdown-item" href="approver_overtime.php">
                                            <i class="fa fa-clock"></i> Overtime 
                                            <span class="badge bg-danger" id="pendingOvertime">0</span>
                                        </a></li>

                                        <li><a class="dropdown-item" href="approver_official_business.php">
                                            <i class="fa fa-briefcase"></i> Official Business 
                                            <span class="badge bg-danger" id="pendingOB">0</span>
                                        </a></li>

                                        <li><a class="dropdown-item" href="approver_change_schedule.php">
                                            <i class="fa fa-calendar-check"></i> Change Schedule 
                                            <span class="badge bg-danger" id="pendingCS">0</span>
                                        </a></li>

                                        <li><a class="dropdown-item" href="approver_failure_clock.php">
                                            <i class="fa fa-exclamation-triangle"></i> Failure to Clock 
                                            <span class="badge bg-danger" id="pendingFC">0</span>
                                        </a></li>

                                        <li><a class="dropdown-item" href="approver_clock_alteration.php">
                                            <i class="fa fa-edit"></i> Clock Alteration 
                                            <span class="badge bg-danger" id="pendingCA">0</span>
                                        </a></li>

                                        <li><a class="dropdown-item" href="approver_work_restday.php">
                                            <i class="fa fa-sun"></i> Work Rest Day 
                                            <span class="badge bg-danger" id="pendingWR">0</span>
                                        </a></li>
                                    </div>
                                </div>
                            </div>
                        </div>

                <footer class="py-4 bg-light mt-auto">
                    <div class="container-fluid px-4">
                        <div class="d-flex align-items-center justify-content-between small">
                            <div class="text-muted">Copyright &copy; Sample System 2025</div>
                            <div>
                                <a href="#">Privacy Policy</a>
                                &middot;
                                <a href="#">Terms &amp; Conditions</a>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/chart-area-demo.js"></script>
        <script src="assets/demo/chart-bar-demo.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="js/datatables-simple-demo.js"></script>

        <!-- Auto-refresh dashboard data every 5 seconds -->
        <script>
            function fetchDashboard() {
                fetch("DASHBOARD_DATA.php")
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            document.getElementById("leaveBalance").innerHTML = "<p>Error: " + data.error + "</p>";
                            return;
                        }

                        // ✅ Update Leave Balance
                        document.getElementById("leaveBalance").innerHTML = `
                            <p><b>Mandatory Leave:</b> ${data.leave?.mandatory ?? 0}</p>
                            <p><b>Vacation Leave:</b> ${data.leave?.vacation_leave ?? 0}</p>
                            <p><b>Sick Leave:</b> ${data.leave?.sick_leave ?? 0}</p>
                        `;

                        // ✅ Update Events
                        let bdays = data.birthdays.length 
                            ? data.birthdays.map(b => `<p>${b.name} (${new Date(b.birthday).toLocaleDateString('en-US',{month:'short',day:'numeric'})})</p>`).join("")
                            : "<p>No birthdays today</p>";

                        let holidays = data.holidays.length
                            ? data.holidays.map(h => `<p><b>${h.title}</b> - ${new Date(h.date).toLocaleDateString()}</p>`).join("")
                            : "<p>No upcoming holidays</p>";

                        document.getElementById("eventSection").innerHTML = `
                            <h6>🎂 Birthdays Today</h6>${bdays}
                            <h6 class="mt-3">📅 Upcoming Holidays</h6>${holidays}
                        `;

                        // ✅ Update Work Schedule
                        if (data.schedule) {
                            document.getElementById("scheduleSection").innerHTML = `
                                <table class="table table-bordered">
                                    <tr><th>Monday</th><td>${data.schedule.monday}</td></tr>
                                    <tr><th>Tuesday</th><td>${data.schedule.tuesday}</td></tr>
                                    <tr><th>Wednesday</th><td>${data.schedule.wednesday}</td></tr>
                                    <tr><th>Thursday</th><td>${data.schedule.thursday}</td></tr>
                                    <tr><th>Friday</th><td>${data.schedule.friday}</td></tr>
                                    <tr><th>Saturday</th><td>${data.schedule.saturday}</td></tr>
                                    <tr><th>Sunday</th><td>${data.schedule.sunday}</td></tr>
                                </table>
                            `;
                        } else {
                            document.getElementById("scheduleSection").innerHTML = "<p>No schedule set</p>";
                        }

                        // ✅ Update Payroll
                        if (data.period) {
                            document.getElementById("payrollSection").innerHTML = `
                                <p><b>Period Code:</b> ${data.period.period_code}</p>
                                <p><b>Start:</b> ${data.period.start_date} | <b>End:</b> ${data.period.end_date}</p>
                                <p><b>Cutoff:</b> ${data.period.cutoff}</p>
                            `;
                        } else {
                            document.getElementById("payrollSection").innerHTML = "<p>No payroll period found</p>";
                        }

                        // ✅ Update Pending Approvals (only for approvers)
                        if (data.pending) {
                            document.getElementById("pendingLeaves").innerText = data.pending.leaves;
                            document.getElementById("pendingOvertime").innerText = data.pending.overtime;
                            document.getElementById("pendingOB").innerText = data.pending.official_business;
                            document.getElementById("pendingCS").innerText = data.pending.change_schedule;
                            document.getElementById("pendingFC").innerText = data.pending.failure_clock;
                            document.getElementById("pendingCA").innerText = data.pending.clock_alteration;
                            document.getElementById("pendingWR").innerText = data.pending.work_restday;
                            document.querySelector(".card.bg-danger").style.display = "block"; // show card
                        } else {
                            document.querySelector(".card.bg-danger").style.display = "none"; // hide card if not approver
                        }
                    });
            }

            // Run immediately and auto-refresh every 5s
            fetchDashboard();
            setInterval(fetchDashboard, 5000);
        </script>

    </body>
</html>
