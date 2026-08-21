<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require_once "../../config/db.php";

header("Content-Type: application/json");
require_api_login('user');

$user_id = intval($_SESSION['user_id']);
$year = intval(date("Y"));

$stmt = $mysqli->prepare("
    SELECT MONTH(entry_time) AS month_number,
           ROUND(IFNULL(SUM(GREATEST(duration_minutes, 0)), 0) / 60, 2) AS hrs
    FROM timings
    WHERE user_id=? AND YEAR(entry_time)=?
    GROUP BY MONTH(entry_time)
    ORDER BY month_number ASC
");
$stmt->bind_param("ii", $user_id, $year);
$stmt->execute();
$res = $stmt->get_result();

$map = [];
while ($row = $res->fetch_assoc()) {
    $map[intval($row['month_number'])] = floatval($row['hrs']);
}

$labels = [];
$hours = [];
for ($month = 1; $month <= 12; $month++) {
    $labels[] = date("M", mktime(0, 0, 0, $month, 1));
    $hours[] = $map[$month] ?? 0;
}

echo json_encode([
    "labels" => $labels,
    "hours" => $hours
]);
?>