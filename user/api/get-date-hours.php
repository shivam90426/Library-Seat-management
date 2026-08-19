<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require_once "../../config/db.php";

header("Content-Type: application/json");

require_api_login('user');

$user_id = intval($_SESSION['user_id']);
$date = normalized_date_or_today($_GET['date'] ?? null);

$stmt = $mysqli->prepare("
SELECT ROUND(IFNULL(SUM(GREATEST(duration_minutes, 0)),0)/60,2) as hrs
FROM timings
WHERE user_id=? AND DATE(entry_time)=?
");

$stmt->bind_param("is",$user_id,$date);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

echo json_encode([
    "hours" => $res['hrs'] ?? 0
]);
