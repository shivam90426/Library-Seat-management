<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require_once "../../config/db.php";

header("Content-Type: application/json");
require_api_login('user');

$user_id = intval($_SESSION['user_id']);
$year = intval(date("Y"));
$month = intval(date("n"));
$daysInMonth = intval(date("t"));

$stmt = $mysqli->prepare("
    SELECT DATE(entry_time) AS day,
           ROUND(IFNULL(SUM(GREATEST(duration_minutes, 0)), 0) / 60, 2) AS hrs
    FROM timings
    WHERE user_id=?
      AND YEAR(entry_time)=?
      AND MONTH(entry_time)=?
    GROUP BY DATE(entry_time)
    ORDER BY day ASC
");
$stmt->bind_param("iii", $user_id, $year, $month);
$stmt->execute();
$res = $stmt->get_result();

$map = [];
while ($row = $res->fetch_assoc()) {
    $map[$row['day']] = floatval($row['hrs']);
}

$labels = [];
$hours = [];
for ($day = 1; $day <= $daysInMonth; $day++) {
    $date = sprintf("%04d-%02d-%02d", $year, $month, $day);
    $labels[] = str_pad((string)$day, 2, "0", STR_PAD_LEFT);
    $hours[] = $map[$date] ?? 0;
}

echo json_encode([
    "labels" => $labels,
    "hours" => $hours
]);
?>