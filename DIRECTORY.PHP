<?php
    session_start();
    require 'db.php';
    if (!isset($_SESSION['user'])) {
        header("Location: LOGIN.php");
        exit();
    }
    $user = $_SESSION['user'];
    $user_id = $_SESSION['user_id'];

    // ✅ Fetch all active users (optional: only show active)
    $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
    $role = isset($_GET['role']) && $_GET['role'] !== '' ? $_GET['role'] : null;

    $sql = "SELECT id, name, address, contact, email, role, profile_pic 
            FROM users 
            WHERE status = 'active' AND name LIKE ?";

    $params = [$search];
    $types = "s";

    if ($role) {
        $sql .= " AND role = ?";
        $params[] = $role;
        $types .= "s";
    }

    $sql .= " ORDER BY name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $userCount = $result->num_rows;

    $countQuery = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
    $totalEmployees = $countQuery->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Companyt Directory</title>
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    </head>

    <style>
            body {
                background: #f8f9fa;
            }
            /* Sidebar + Layout */
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
                margin: 30px 30px 30px 30px;  
                /* left = sidebar (250px) + gap (20px) */
            }
            /* Directory cards */
            .directory-card {
                border-radius: 12px;
                box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                transition: transform 0.2s ease;
                padding: 20px;
                height: 100%;
            }
            .directory-card:hover {
                transform: translateY(-3px);
            }
            .profile-pic {
                width: 100px;
                height: 100px;
                object-fit: cover;
                border-radius: 50%;
                border: 2px solid #ddd;
            }
        </style>

    <body>
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
                            <a class="nav-link" href="#">
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
        <h2 class="mb-4 text-left">Company Directory</h2>
        <br>
        <form method="GET" class="row mb-4">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Search by name" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            </div>
            <div class="col-md-4">
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <option value="admin" <?= (isset($_GET['role']) && $_GET['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="superadmin" <?= (isset($_GET['role']) && $_GET['role'] === 'superadmin') ? 'selected' : '' ?>>Superadmin</option>
                    <option value="user" <?= (isset($_GET['role']) && $_GET['role'] === 'user') ? 'selected' : '' ?>>User</option>
                    <!-- Add other roles as needed -->
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </form>
            <p class="text-muted">No. of Records Found: <strong><?= $userCount ?></strong></p>

        <div class="row g-4">
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card directory-card text-start p-4">
                        <img src="<?= !empty($row['profile_pic']) ? 'uploads/' . $row['profile_pic'] : 'uploads/default.png' ?>" 
                             class="profile-pic mx-auto" alt="Profile">
                        <h5><?= htmlspecialchars($row['name']) ?></h5>
                        <p class="text-muted"><?= htmlspecialchars($row['role']) ?></p>
                        <p>
                            📧 
                            <span id="email-<?= $row['id'] ?>"><?= htmlspecialchars($row['email']) ?></span>
                            <button class="btn btn-sm btn-outline-secondary copy-btn" 
                                    data-email="<?= htmlspecialchars($row['email']) ?>">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </p>
                        <script>
                            document.querySelectorAll('.copy-btn').forEach(button => {
                                button.addEventListener('click', () => {
                                    const email = button.getAttribute('data-email');
                                    navigator.clipboard.writeText(email).then(() => {
                                        // Optional: show feedback
                                        button.innerHTML = '✔'; // check mark
                                        setTimeout(() => {
                                            button.innerHTML = '<i class="fa-regular fa-copy"></i>';
                                        }, 1000);
                                    }).catch(err => {
                                        alert('Failed to copy email');
                                    });
                                });
                            });
                        </script>
                        <p>📞 <span id="contact-<?= $row['id'] ?>"><?= htmlspecialchars($row['contact']) ?></span>
                            <button class="btn btn-sm btn-outline-secondary copy-btn" 
                                    data-contact="<?= htmlspecialchars($row['contact']) ?>">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </p>
                        <script>
                            document.querySelectorAll('.copy-btn').forEach(button => {
                                button.addEventListener('click', () => {
                                    const contact = button.getAttribute('data-contact');
                                    navigator.clipboard.writeText(contact).then(() => {
                                        // Optional: show feedback
                                        button.innerHTML = '✔'; // check mark
                                        setTimeout(() => {
                                            button.innerHTML = '<i class="fa-regular fa-copy"></i>';
                                        }, 1000);
                                    }).catch(err => {
                                        alert('Failed to copy contact');
                                    });
                                });
                            });
                        </script>
                        <p>🏠 <small class="text-secondary"><?= htmlspecialchars($row['address']) ?></small></p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
    </body>
</html>
