<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Leave Balance
$leave_sql = "SELECT mandatory, vacation_leave, sick_leave FROM leave_credits WHERE user_id = ?";
$stmt = $conn->prepare($leave_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$leave = $stmt->get_result()->fetch_assoc();

// Birthdays today
$today = date("Y-m-d");
$bday_sql = "SELECT name, birthday FROM users WHERE DATE_FORMAT(birthday, '%m-%d') = DATE_FORMAT(?, '%m-%d')";
$stmt = $conn->prepare($bday_sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$birthdays = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Holidays next 7 days
$holiday_sql = "SELECT * FROM holidays WHERE date >= ? ORDER BY date ASC LIMIT 5";
$stmt = $conn->prepare($holiday_sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$holidays = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Work schedule
$sched_sql = "SELECT * FROM employee_schedules WHERE user_id = ?";
$stmt = $conn->prepare($sched_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_assoc();

// Payroll period
$period_sql = "SELECT * FROM payroll_periods ORDER BY end_date DESC LIMIT 1";
$period = $conn->query($period_sql)->fetch_assoc();

// Return JSON
echo json_encode([
    'leave' => $leave,
    'birthdays' => $birthdays,
    'holidays' => $holidays,
    'schedule' => $schedule,
    'period' => $period
]);