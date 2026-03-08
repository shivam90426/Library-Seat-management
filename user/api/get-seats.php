<?php
session_start();
require_once "../../config/db.php";

if(!isset($_SESSION['user_id'])){
    exit;
}

$user_id = intval($_SESSION['user_id']);
$today = date("Y-m-d");

/*
Get all seats + check booking status
*/
$query = "
SELECT 
    s.id,
    s.seat_no,
    s.seat_type,
    s.section_name,
    s.is_active,
    s.is_maintenance,

    sb.user_id AS booked_user,
    sb.booking_type,
    sb.booking_date,
    sb.status

FROM seats s

LEFT JOIN seat_bookings sb 
    ON sb.seat_id = s.id
    AND sb.status = 'active'
    AND (
        (sb.booking_type='daily' AND sb.booking_date='$today')
        OR
        (sb.booking_type='fixed')
    )

ORDER BY s.seat_type, s.position_order
";

$result = $mysqli->query($query);

$seats = [];

while($row = $result->fetch_assoc()){

    $status = "available";

    if(!$row['is_active'] || $row['is_maintenance']){
        $status = "blocked";
    }
    elseif($row['booked_user']){
        if($row['booked_user'] == $user_id){
            $status = "mine";
        }else{
            $status = "booked";
        }
    }

    $seats[] = [
        "id"=>$row['id'],
        "seat_no"=>$row['seat_no'],
        "seat_type"=>$row['seat_type'],
        "section"=>$row['section_name'],
        "status"=>$status
    ];
}

echo json_encode($seats);