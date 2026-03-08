
<?php
session_start();
require_once "../../config/db.php";

if(!isset($_SESSION['user_id'])){
    exit;
}


$user_id = $_SESSION['user_id'];
/* Get Plan Hours */
$sub = $mysqli->prepare("
SELECT seat_type 
FROM subscriptions
WHERE user_id=? AND status='active'
ORDER BY id DESC LIMIT 1
");
$sub->bind_param("i",$user_id);
$sub->execute();
$planRow = $sub->get_result()->fetch_assoc();

$maxHours = $planRow ? intval($planRow['seat_type']) : 6;

/* Get Monday of current week */
$monday = date("Y-m-d", strtotime("monday this week"));
$sunday = date("Y-m-d", strtotime("sunday this week"));

$stmt = $mysqli->prepare("
SELECT DATE(entry_time) as day,
SUM(duration_minutes) as total_minutes
FROM timings
WHERE user_id=?
AND DATE(entry_time) BETWEEN ? AND ?
GROUP BY DATE(entry_time)
");
$stmt->bind_param("iss",$user_id,$monday,$sunday);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while($row = $result->fetch_assoc()){
    $data[$row['day']] = round($row['total_minutes']/60,2);
}

/* Build full week array */
$labels = [];
$hours = [];

for($i=0;$i<7;$i++){
    $date = date("Y-m-d", strtotime($monday . " +$i days"));
    $labels[] = date("D", strtotime($date));
    $hours[] = isset($data[$date]) ? $data[$date] : 0;
}

echo json_encode([
    "labels"=>$labels,
    "hours"=>$hours,
    "max"=>$maxHours
]);