<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require_request_method('POST');
require_csrf_token();
require_once "../../config/db.php";

if(empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user'){
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $mysqli->prepare("
SELECT id, entry_time 
FROM timings
WHERE user_id=? AND exit_time IS NULL
LIMIT 1
");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if(!$row){
    echo "noactive";
    exit;
}

$id = $row['id'];
$entry_time = $row['entry_time'];

$minutes = floor((time() - strtotime($entry_time)) / 60);
if ($minutes < 0) {
    $minutes = 0;
}

$update = $mysqli->prepare("
UPDATE timings 
SET exit_time=NOW(),
duration_minutes=?
WHERE id=?
");
$update->bind_param("ii",$minutes,$id);
$update->execute();

echo "stopped";
