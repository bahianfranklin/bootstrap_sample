<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: LOGIN.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];

// Fetch payroll periods
$sql = "SELECT id, period_code, start_date, end_date 
        FROM payroll_periods 
        WHERE status = 'Open' 
        ORDER BY start_date DESC";
$result = $conn->query($sql);

if (isset($_GET['period_id'])) {
    $period_id = $_GET['period_id'];

    // Get selected period
    $stmt = $conn->prepare("SELECT start_date, end_date FROM payroll_periods WHERE id = ?");
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    $period = $stmt->get_result()->fetch_assoc();

    $start_date = $period['start_date'];
    $end_date = $period['end_date'];

    // Fetch attendance for this user in the period
    $sql = "SELECT * FROM attendance_logs 
            WHERE user_id = ? AND log_date BETWEEN ? AND ?
            ORDER BY log_date ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt->execute();
    $attendance = $stmt->get_result();

    // Define sample schedule (or pull from DB in real app)
    $schedule = [
        'monday'    => 'work_day',
        'tuesday'   => 'work_day',
        'wednesday' => 'work_day',
        'thursday'  => 'work_day',
        'friday'    => 'work_day',
        'saturday'  => 'rest_day',
        'sunday'    => 'rest_day'
    ];

    // Rewind and calculate total hours
    $totalHours = 0;
    $attendance->data_seek(0);
    while ($row = $attendance->fetch_assoc()) {
        if ($row['login_time'] && $row['logout_time']) {
            $totalHours += (strtotime($row['logout_time']) - strtotime($row['login_time'])) / 3600;
        }
    }

    // Calendar rendering function
    function renderCalendar($start_date, $end_date, $attendance_logs, $schedule) {
        $attendance_by_date = [];
        while ($row = $attendance_logs->fetch_assoc()) {
            $attendance_by_date[$row['log_date']] = $row;
        }

        $current = strtotime($start_date);
        $end = strtotime($end_date);

        echo "<table border='1' cellpadding='5'><tr>";
        echo "<th>Date</th><th>Status</th><th>Hours</th></tr>";

        while ($current <= $end) {
            $date = date("Y-m-d", $current);
            $day = strtolower(date("l", $current)); // e.g., monday

            $status = "Absent";
            $hours = 0;

            if (isset($attendance_by_date[$date])) {
                $row = $attendance_by_date[$date];
                if ($row['login_time'] && $row['logout_time']) {
                    $hours = (strtotime($row['logout_time']) - strtotime($row['login_time'])) / 3600;
                }
                $status = "Present (" . ucfirst($row['work_type']) . ")";
            } elseif (isset($schedule[$day]) && $schedule[$day] == 'rest_day') {
                $status = "Rest Day";
            }

            echo "<tr><td>$date</td><td>$status</td><td>" . number_format($hours, 2) . " hrs</td></tr>";

            $current = strtotime("+1 day", $current);
        }

        echo "</table>";
    }
}
?>

<!-- UI: Payroll Period Selection -->
<form method="GET" action="">
    <label for="period">Select Payroll Period:</label>
    <select name="period_id" id="period" required>
        <option value="">-- Select Period --</option>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <option value="<?= $row['id']; ?>" <?= (isset($_GET['period_id']) && $_GET['period_id'] == $row['id']) ? 'selected' : '' ?>>
                <?= $row['period_code'] ?> (<?= $row['start_date'] ?> to <?= $row['end_date'] ?>)
            </option>
        <?php } ?>
    </select>
    <button type="submit">View</button>
</form>

<!-- Output Attendance Table -->
<?php if (isset($attendance)) { ?>
    <h3>Attendance Records for <?= htmlspecialchars($user['name']) ?></h3>
    <table border="1" cellpadding="5">
        <tr>
            <th>Date</th>
            <th>Login</th>
            <th>Logout</th>
            <th>Work Type</th>
            <th>Hours Worked</th>
        </tr>
        <?php 
        $attendance->data_seek(0);
        while ($row = $attendance->fetch_assoc()) {
            $hours = 0;
            if ($row['login_time'] && $row['logout_time']) {
                $hours = (strtotime($row['logout_time']) - strtotime($row['login_time'])) / 3600;
            }
        ?>
            <tr>
                <td><?= $row['log_date'] ?></td>
                <td><?= $row['login_time'] ?></td>
                <td><?= $row['logout_time'] ?></td>
                <td><?= ucfirst($row['work_type']) ?></td>
                <td><?= number_format($hours, 2) ?> hrs</td>
            </tr>
        <?php } ?>
    </table>

    <h3>Total Hours Worked: <?= number_format($totalHours, 2) ?> hrs</h3>

    <h3>Calendar View</h3>
    <?php 
        $attendance->data_seek(0);
        renderCalendar($start_date, $end_date, $attendance, $schedule); 
    ?>
<?php } ?>
