<?php
include  __DIR__ . '/layout/header.php';
?>
<body class="sb-nav-fixed">
    <?php include __DIR__.'/layout/page_header.php'; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include __DIR__.'/layout/navigation.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <?php include __DIR__.'/contact-page.php'; ?>
            </main>
        </div>
    </div>      
</body>