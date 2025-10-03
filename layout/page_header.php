<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <!-- Navbar Brand-->
    <a class="navbar-brand ps-3 text-success" href="INDEX.php">New Logo</a>
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