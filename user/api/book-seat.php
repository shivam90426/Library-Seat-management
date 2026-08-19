<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require_request_method('POST');
require_csrf_token();
require_once "../../config/db.php";

require_api_login('user');

$user_id = intval($_SESSION['user_id']);
$seat_id = intval($_POST['seat_id'] ?? 0);
$today = date("Y-m-d");

if ($seat_id <= 0) {
    echo "invalid";
    exit;
}

$sub = $mysqli->prepare("
SELECT id, seat_type, end_date
FROM subscriptions
WHERE user_id=? AND status='active'
ORDER BY id DESC LIMIT 1
");
$sub->bind_param("i", $user_id);
$sub->execute();
$subRow = $sub->get_result()->fetch_assoc();

if (!$subRow || strtotime($subRow['end_date']) < strtotime($today)) {
    echo "expired";
    exit;
}

$subscription_id = intval($subRow['id']);
$seat_type = $subRow['seat_type'];
$booking_type = $seat_type === "6h" ? "daily" : "fixed";

$check = $mysqli->prepare("
SELECT id
FROM seat_bookings
WHERE user_id=?
AND status='active'
AND (
    (booking_type='daily' AND booking_date=?)
    OR
    (booking_type='fixed')
)
LIMIT 1
");
$check->bind_param("is", $user_id, $today);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    echo "already";
    exit;
}

$seatCheck = $mysqli->prepare("
SELECT id
FROM seat_bookings
WHERE seat_id=?
AND status='active'
AND (
    (booking_type='daily' AND booking_date=?)
    OR
    (booking_type='fixed')
)
LIMIT 1
");
$seatCheck->bind_param("is", $seat_id, $today);
$seatCheck->execute();

if ($seatCheck->get_result()->num_rows > 0) {
    echo "taken";
    exit;
}

$stmt = $mysqli->prepare("
INSERT INTO seat_bookings
(seat_id, user_id, subscription_id, booking_type, booking_date, booking_start, status)
VALUES (?, ?, ?, ?, ?, NOW(), 'active')
");
$stmt->bind_param(
    "iiiss",
    $seat_id,
    $user_id,
    $subscription_id,
    $booking_type,
    $today
);
$stmt->execute();

echo $stmt->affected_rows > 0 ? "booked" : "error";
?>
