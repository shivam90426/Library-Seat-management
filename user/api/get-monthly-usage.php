<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require_once "../../config/db.php";

header("Content-Type: application/json");

require_api_login('user');

$user_id = intval($_SESSION['user_id']);

$stmt = $mysqli->prepare("
SELECT MONTH(entry_time) AS month_number,
ROUND(IFNULL(SUM(GREATEST(duration_minutes, 0)), 0) / 60, 2) AS hrs
FROM timings
WHERE user_id=?
AND YEAR(entry_time)=YEAR(CURDATE())
GROUP BY MONTH(entry_time)
ORDER BY month_number ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$labels = [];
$hours = [];

while ($row = $res->fetch_assoc()) {
    $labels[] = date("M", mktime(0, 0, 0, intval($row['month_number']), 1));
    $hours[] = floatval($row['hrs']);
}

echo json_encode([
    "labels" => $labels,
    "hours" => $hours
]);
