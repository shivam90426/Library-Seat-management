<?php
session_start();
require_once "../../config/db.php";

if(!isset($_SESSION['user_id'])){
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

/* insert/update timings */
$date = date("Y-m-d");

$check = $mysqli->prepare("
SELECT id,total_minutes FROM timings
WHERE user_id=? AND date=?
");
$check->bind_param("is",$user_id,$date);
$check->execute();
$existing = $check->get_result()->fetch_assoc();

if($existing){
    $newMinutes = $existing['total_minutes'] + $minutes;
    $up = $mysqli->prepare("
    UPDATE timings SET total_minutes=? WHERE id=?
    ");
    $up->bind_param("ii",$newMinutes,$existing['id']);
    $up->execute();
}else{
    $ins = $mysqli->prepare("
    INSERT INTO timings (user_id,date,total_minutes)
    VALUES (?,?,?)
    ");
    $ins->bind_param("isi",$user_id,$date,$minutes);
    $ins->execute();
}

echo json_encode(["status"=>"success"]);