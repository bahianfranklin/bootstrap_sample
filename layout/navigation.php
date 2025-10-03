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
            <a class="nav-link" href="MAINTENANCE.php">
                <div class="sb-nav-link-icon"><i class="fas fa-toolbox"></i></div>
                New Link
            </a>
        </div>
    </div>
    <div class="sb-sidenav-footer">
        <div class="small">Logged in as:</div>
        Start Bootstrap
    </div>
</nav>