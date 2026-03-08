<?php
session_start();
require_once "../../config/db.php";

$user_id = $_SESSION['user_id'];
$seat_id = intval($_POST['seat_id']);
$today = date("Y-m-d");

/* Check subscription */

$sub = $mysqli->prepare("
SELECT id, seat_type, end_date
FROM subscriptions
WHERE user_id=? AND status='active'
ORDER BY id DESC LIMIT 1
");

$sub->bind_param("i",$user_id);
$sub->execute();
$subRow=$sub->get_result()->fetch_assoc();

if(!$subRow){
echo "expired";
exit;
}

if(strtotime($subRow['end_date']) < strtotime($today)){
echo "expired";
exit;
}

$subscription_id=$subRow['id'];
$seat_type=$subRow['seat_type'];

/* check already booked today */

$check = $mysqli->prepare("
SELECT id FROM seat_bookings
WHERE user_id=? AND booking_date=? AND status='active'
");

$check->bind_param("is",$user_id,$today);
$check->execute();

if($check->get_result()->num_rows>0){
echo "already";
exit;
}

/* seat taken? */

$seatCheck=$mysqli->prepare("
SELECT id FROM seat_bookings
WHERE seat_id=? AND booking_date=? AND status='active'
");

$seatCheck->bind_param("is",$seat_id,$today);
$seatCheck->execute();

if($seatCheck->get_result()->num_rows>0){
echo "taken";
exit;
}

/* insert booking */

$type = $seat_type=="6h" ? "daily" : "fixed";

$stmt=$mysqli->prepare("
INSERT INTO seat_bookings
(seat_id,user_id,subscription_id,booking_type,booking_date,booking_start)
VALUES (?,?,?,?,?,NOW())
");

$stmt->bind_param("iiiss",
$seat_id,
$user_id,
$subscription_id,
$type,
$today
);

$stmt->execute();

echo "booked";