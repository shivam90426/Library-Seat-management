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

/* check active subscription */
$sub = $mysqli->prepare("
SELECT end_date FROM subscriptions 
WHERE user_id=? AND status='active'
ORDER BY id DESC LIMIT 1
");
$sub->bind_param("i",$user_id);
$sub->execute();
$res = $sub->get_result()->fetch_assoc();

if(!$res || strtotime($res['end_date']) < strtotime(date("Y-m-d"))){
    echo "expired";
    exit;
}

/* check if already running */
$check = $mysqli->prepare("
SELECT id FROM timings 
WHERE user_id=? AND exit_time IS NULL
LIMIT 1
");
$check->bind_param("i",$user_id);
$check->execute();
if($check->get_result()->num_rows > 0){
    echo "already";
    exit;
}

/* insert new entry */
$stmt = $mysqli->prepare("
INSERT INTO timings (user_id, entry_time)
VALUES (?, NOW())
");
$stmt->bind_param("i",$user_id);
$stmt->execute();

echo "started";
