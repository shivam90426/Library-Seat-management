<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require_request_method('POST');
require_csrf_token();
require_once "../../config/db.php";

if(empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user'){
    echo json_encode(["status"=>"error","msg"=>"Not logged in"]);
    exit;
}

$user_id = intval($_SESSION['user_id']);

/* check already active */
$check = $mysqli->prepare("
SELECT id FROM attendance 
WHERE user_id=? AND status='active'
");
$check->bind_param("i",$user_id);
$check->execute();
if($check->get_result()->num_rows > 0){
    echo json_encode(["status"=>"error","msg"=>"Already active"]);
    exit;
}

/* insert entry */
$stmt = $mysqli->prepare("
INSERT INTO attendance (user_id, entry_time, status)
VALUES (?, NOW(), 'active')
");
$stmt->bind_param("i",$user_id);
$stmt->execute();

echo json_encode(["status"=>"success"]);
