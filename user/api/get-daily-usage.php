<?php
session_start();
require_once "../../config/db.php";

$user_id = intval($_SESSION['user_id']);

$stmt = $mysqli->prepare("
SELECT DATE(entry_time) as day,
ROUND(SUM(duration_minutes)/60,2) as hrs
FROM timings
WHERE user_id=?
AND MONTH(entry_time)=MONTH(CURDATE())
AND YEAR(entry_time)=YEAR(CURDATE())
GROUP BY DATE(entry_time)
ORDER BY day ASC
");

$stmt->bind_param("i",$user_id);
$stmt->execute();
$res = $stmt->get_result();

$labels = [];
$hours = [];

while($row = $res->fetch_assoc()){
    $labels[] = date("d",strtotime($row['date']));
    $hours[] = floatval($row['hrs']);
}

echo json_encode([
    "labels"=>$labels,
    "hours"=>$hours
]);