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
    $result = $stmt->get_result();
    $leave = $result->fetch_assoc();

    // ✅ Get Events
    $today = date("Y-m-d");

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
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Dashboard - SAMPLE SYSTEM</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    </head>