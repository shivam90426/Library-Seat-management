<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require_once "../../config/db.php";

header("Content-Type: application/json");

require_api_login('user');

$user_id = intval($_SESSION['user_id']);

$stmt = $mysqli->prepare("
SELECT DATE(entry_time) AS day,
ROUND(IFNULL(SUM(GREATEST(duration_minutes, 0)), 0) / 60, 2) AS hrs
FROM timings
WHERE user_id=?
AND MONTH(entry_time)=MONTH(CURDATE())
AND YEAR(entry_time)=YEAR(CURDATE())
GROUP BY DATE(entry_time)
ORDER BY day ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$labels = [];
$hours = [];

while ($row = $res->fetch_assoc()) {
    $labels[] = date("d", strtotime($row['day']));
    $hours[] = floatval($row['hrs']);
}

echo json_encode([
    "labels" => $labels,
    "hours" => $hours
]);
