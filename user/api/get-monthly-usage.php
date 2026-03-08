<?php
session_start();
require_once "../../config/db.php";

$user_id = intval($_SESSION['user_id']);

$stmt = $mysqli->prepare("
SELECT MONTH(date) as month,
ROUND(SUM(total_minutes)/60,2) as hrs
FROM timings
WHERE user_id=? 
AND YEAR(date)=YEAR(CURDATE())
GROUP BY MONTH(date)
ORDER BY month ASC
");

$stmt->bind_param("i",$user_id);
$stmt->execute();
$res = $stmt->get_result();

$labels = [];
$hours = [];

while($row = $res->fetch_assoc()){
    $labels[] = date("M", mktime(0,0,0,$row['month'],1));
    $hours[] = floatval($row['hrs']);
}

echo json_encode([
    "labels"=>$labels,
    "hours"=>$hours
]);