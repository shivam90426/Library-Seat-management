<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require_once "../../config/db.php";

header("Content-Type: application/json; charset=utf-8");
require_api_login('user');

$user_id = intval($_SESSION['user_id']);
$today = date('Y-m-d');

$subStmt = $mysqli->prepare("
    SELECT seat_type, end_date
    FROM subscriptions
    WHERE user_id=?
      AND status='active'
    ORDER BY id DESC
    LIMIT 1
");
$subStmt->bind_param('i', $user_id);
$subStmt->execute();
$subscription = $subStmt->get_result()->fetch_assoc();

$userPlan = $subscription ? strtolower(trim($subscription['seat_type'])) : null;
$bookingAllowed = $subscription && $subscription['end_date'] >= $today;

// Fetch all active bookings for today. We aggregate them in PHP so a 6H
// seat can independently have a morning and an evening booking.
$bookingStmt = $mysqli->prepare("
    SELECT seat_id, user_id, booking_type, booking_start, booking_end
    FROM seat_bookings
    WHERE status='active'
      AND (
          (booking_type='daily' AND booking_date=?)
          OR booking_type='fixed'
      )
");
$bookingStmt->bind_param('s', $today);
$bookingStmt->execute();
$bookingResult = $bookingStmt->get_result();

$bookings = [];
$userBooking = null;

while ($row = $bookingResult->fetch_assoc()) {
    $seatId = (int)$row['seat_id'];
    if (!isset($bookings[$seatId])) {
        $bookings[$seatId] = [];
    }
    $bookings[$seatId][] = $row;

    if ((int)$row['user_id'] === $user_id) {
        $userBooking = $row;
    }
}

$sectionStmt = $mysqli->prepare("
    SELECT id, name, section_code, pos_x, pos_y, width, height
    FROM seat_sections
    ORDER BY pos_y, pos_x, id
");
$sectionStmt->execute();
$sectionResult = $sectionStmt->get_result();

$sections = [];
while ($sectionRow = $sectionResult->fetch_assoc()) {
    $code = (string)$sectionRow['section_code'];
    $seatTypeMap = [
        'six' => '6h',
        'twelve_left' => '12h',
        'twelve_mid' => '12h',
        'twentyfour' => '24h'
    ];
    $sections[$code] = [
        'id' => (int)$sectionRow['id'],
        'name' => $sectionRow['name'],
        'section_code' => $code,
        'seat_type' => $seatTypeMap[$code] ?? '6h',
        'pos_x' => (float)$sectionRow['pos_x'],
        'pos_y' => (float)$sectionRow['pos_y'],
        'width' => (int)$sectionRow['width'],
        'height' => (int)$sectionRow['height'],
        'seats' => []
    ];
}

$seatStmt = $mysqli->prepare("
    SELECT id, seat_no, seat_type, section_name, position_order, is_active, is_maintenance
    FROM seats
    ORDER BY section_name, position_order
");
$seatStmt->execute();
$seatResult = $seatStmt->get_result();

$data = [];

while ($seat = $seatResult->fetch_assoc()) {
    $seatId = (int)$seat['id'];
    $seatType = strtolower(trim($seat['seat_type']));
    $status = 'available';
    $morning = 'available';
    $evening = 'available';

    if (!$seat['is_active'] || $seat['is_maintenance']) {
        $status = 'blocked';
        $morning = 'blocked';
        $evening = 'blocked';
    } elseif (!$bookingAllowed || !$userPlan) {
        $status = 'unavailable_plan';
        $morning = 'unavailable_plan';
        $evening = 'unavailable_plan';
    } elseif ($seatType !== $userPlan) {
        // Wrong category is intentionally disabled for this subscription.
        $status = 'unavailable_plan';
        $morning = 'unavailable_plan';
        $evening = 'unavailable_plan';
    } elseif ($userBooking) {
        // User already has their allowed booking for today/plan.
        if ((int)$userBooking['seat_id'] === $seatId) {
            $status = 'mine';

            if ($userPlan === '6h') {
                $start = date('H:i:s', strtotime($userBooking['booking_start']));
                if ($start === '06:00:00') {
                    $morning = 'mine';
                    $evening = 'available';
                } else {
                    $morning = 'available';
                    $evening = 'mine';
                }
            } else {
                $morning = 'mine';
                $evening = 'mine';
            }
        } else {
            $status = 'unavailable_plan';
            $morning = 'unavailable_plan';
            $evening = 'unavailable_plan';
        }
    } elseif ($seatType === '6h') {
        foreach ($bookings[$seatId] ?? [] as $booking) {
            if ($booking['booking_type'] !== 'daily') {
                continue;
            }

            $start = date('H:i:s', strtotime($booking['booking_start']));
            $bookingStatus = ((int)$booking['user_id'] === $user_id) ? 'mine' : 'booked';

            if ($start === '06:00:00') {
                $morning = $bookingStatus;
            } elseif ($start === '12:00:00') {
                $evening = $bookingStatus;
            }
        }

        if ($morning === 'mine' || $evening === 'mine') {
            $status = 'mine';
        } elseif ($morning === 'booked' && $evening === 'booked') {
            $status = 'booked';
        } else {
            $status = 'available';
        }
    } else {
        // 12H/24H: a fixed booking occupies the whole seat.
        if (!empty($bookings[$seatId])) {
            $status = 'booked';
            foreach ($bookings[$seatId] as $booking) {
                if ((int)$booking['user_id'] === $user_id) {
                    $status = 'mine';
                    break;
                }
            }
        }
        $morning = $status;
        $evening = $status;
    }

    $data[] = [
        'id' => $seatId,
        'seat_no' => $seat['seat_no'],
        'seat_type' => $seatType,
        'section' => $seat['section_name'],
        'status' => $status,
        'morning' => $morning,
        'evening' => $evening
    ];
}

foreach ($data as $seat) {
    $code = $seat['section'];
    if (isset($sections[$code])) {
        $sections[$code]['seats'][] = $seat;
    }
}

echo json_encode([
    'sections' => array_values($sections),
    'seats' => $data
]);
?>
