<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require_request_method('POST');
require_csrf_token();
require_once "../../config/db.php";

require_api_login('user');

header('Content-Type: text/plain; charset=utf-8');

$user_id = intval($_SESSION['user_id']);
$seat_id = intval($_POST['seat_id'] ?? 0);
$shift = strtolower(trim($_POST['shift'] ?? ''));
$today = date('Y-m-d');

if ($seat_id <= 0) {
    echo 'invalid';
    exit;
}

$sub = $mysqli->prepare("
    SELECT id, seat_type, end_date
    FROM subscriptions
    WHERE user_id=?
      AND status='active'
    ORDER BY id DESC
    LIMIT 1
");
$sub->bind_param('i', $user_id);
$sub->execute();
$subRow = $sub->get_result()->fetch_assoc();

if (!$subRow || $subRow['end_date'] < $today) {
    echo 'expired';
    exit;
}

$subscription_id = intval($subRow['id']);
$user_seat_type = strtolower(trim($subRow['seat_type']));

if (!in_array($user_seat_type, ['6h', '12h', '24h'], true)) {
    echo 'invalid_plan';
    exit;
}

$seat = $mysqli->prepare("
    SELECT id, seat_type, is_active, is_maintenance
    FROM seats
    WHERE id=?
    LIMIT 1
");
$seat->bind_param('i', $seat_id);
$seat->execute();
$seatRow = $seat->get_result()->fetch_assoc();

if (!$seatRow) {
    echo 'invalid';
    exit;
}

if (!$seatRow['is_active'] || $seatRow['is_maintenance']) {
    echo 'blocked';
    exit;
}

$seat_type = strtolower(trim($seatRow['seat_type']));

// A subscription can book only seats from the same category.
if ($seat_type !== $user_seat_type) {
    echo 'wrong_category';
    exit;
}

if ($user_seat_type === '6h') {
    if (!in_array($shift, ['morning', 'evening'], true)) {
        echo 'shift_required';
        exit;
    }

    $booking_type = 'daily';

    if ($shift === 'morning') {
        $booking_start = $today . ' 06:00:00';
        $booking_end   = $today . ' 12:00:00';
    } else {
        $booking_start = $today . ' 12:00:00';
        $booking_end   = $today . ' 18:00:00';
    }
} else {
    // 12H and 24H plans are fixed-seat bookings for the active subscription.
    $booking_type = 'fixed';
    $booking_start = date('Y-m-d H:i:s');
    $booking_end   = $subRow['end_date'] . ' 23:59:59';
}

$mysqli->begin_transaction();

try {
    // One active seat booking per user at a time/day.
    if ($booking_type === 'daily') {
        $check = $mysqli->prepare("
            SELECT id
            FROM seat_bookings
            WHERE user_id=?
              AND status='active'
              AND booking_type='daily'
              AND booking_date=?
            LIMIT 1
        ");
        $check->bind_param('is', $user_id, $today);
    } else {
        $check = $mysqli->prepare("
            SELECT id
            FROM seat_bookings
            WHERE user_id=?
              AND status='active'
              AND booking_type='fixed'
            LIMIT 1
        ");
        $check->bind_param('i', $user_id);
    }

    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $mysqli->rollback();
        echo 'already';
        exit;
    }

    // Check whether the requested seat is occupied during the requested period.
    if ($booking_type === 'daily') {
        $seatCheck = $mysqli->prepare("
            SELECT id
            FROM seat_bookings
            WHERE seat_id=?
              AND status='active'
              AND booking_type='daily'
              AND booking_date=?
              AND booking_start < ?
              AND booking_end > ?
            LIMIT 1
        ");
        $seatCheck->bind_param('isss', $seat_id, $today, $booking_end, $booking_start);
    } else {
        $seatCheck = $mysqli->prepare("
            SELECT id
            FROM seat_bookings
            WHERE seat_id=?
              AND status='active'
              AND booking_type='fixed'
            LIMIT 1
        ");
        $seatCheck->bind_param('i', $seat_id);
    }

    $seatCheck->execute();
    if ($seatCheck->get_result()->num_rows > 0) {
        $mysqli->rollback();
        echo $booking_type === 'daily' ? 'shift_taken' : 'taken';
        exit;
    }

    $stmt = $mysqli->prepare("
        INSERT INTO seat_bookings
        (seat_id, user_id, subscription_id, booking_type, booking_date, booking_start, booking_end, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    $stmt->bind_param(
        'iiissss',
        $seat_id,
        $user_id,
        $subscription_id,
        $booking_type,
        $today,
        $booking_start,
        $booking_end
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $mysqli->commit();
    echo 'booked';
} catch (Throwable $e) {
    $mysqli->rollback();
    echo 'error';
}
?>
