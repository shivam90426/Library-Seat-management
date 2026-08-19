<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require_request_method('POST');
require_csrf_token();
require_once "../../config/db.php";

if(empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user'){
    echo json_encode(["status"=>"error"]);
    exit;
}

$user_id = intval($_SESSION['user_id']);

/* get active session */
$stmt = $mysqli->prepare("
SELECT id, entry_time 
FROM attendance
WHERE user_id=? AND status='active'
ORDER BY id DESC LIMIT 1
");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if(!$res){
    echo json_encode(["status"=>"error","msg"=>"No active session"]);
    exit;
}

$attendance_id = $res['id'];
$entry_time = $res['entry_time'];

/* calculate minutes */
$minutes = round((time() - strtotime($entry_time)) / 60);

/* update attendance */
$update = $mysqli->prepare("
UPDATE attendance 
SET exit_time=NOW(), status='completed'
WHERE id=?
");
$update->bind_param("i",$attendance_id);
$update->execute();

/* store timing in the same schema used by the dashboard analytics */
$timing = $mysqli->prepare("
INSERT INTO timings (user_id, entry_time, exit_time, duration_minutes)
VALUES (?, ?, NOW(), ?)
");
$timing->bind_param("isi", $user_id, $entry_time, $minutes);
$timing->execute();

echo json_encode(["status"=>"success"]);
