<?php
session_start();
require_once "../../config/db.php";

$user_id = intval($_SESSION['user_id'] ?? 0);
$today = date("Y-m-d");

$query = "
SELECT 
s.id,
s.seat_no,
s.seat_type,
s.section_name,
s.position_order,
s.is_active,
s.is_maintenance,

sb.user_id AS booked_user

FROM seats s

LEFT JOIN seat_bookings sb 
ON sb.seat_id = s.id
AND sb.status='active'
AND (
(sb.booking_type='daily' AND sb.booking_date='$today')
OR
(sb.booking_type='fixed')
)

ORDER BY s.section_name, s.position_order
";

$res = $mysqli->query($query);

$data = [];

while($row = $res->fetch_assoc()){

$status="available";

if(!$row['is_active'] || $row['is_maintenance']){
$status="blocked";
}
elseif($row['booked_user']){
$status = ($row['booked_user']==$user_id) ? "mine":"booked";
}

$data[]=[
"id"=>$row['id'],
"seat_no"=>$row['seat_no'],
"seat_type"=>$row['seat_type'],
"section"=>$row['section_name'],
"status"=>$status
];
}

echo json_encode($data);