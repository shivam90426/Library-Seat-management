<?php
session_start();
require_once "../../config/db.php";

$user_id = intval($_SESSION['user_id']);
$date = $_GET['date'];

$stmt = $mysqli->prepare("
SELECT ROUND(IFNULL(SUM(duration_minutes),0)/60,2) as hrs
FROM timings
WHERE user_id=? AND DATE(entry_time)=?
");

$stmt->bind_param("is",$user_id,$date);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

echo json_encode([
    "hours" => $res['hrs'] ?? 0
]);