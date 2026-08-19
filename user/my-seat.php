<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    header("Location: ../login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$today = date("Y-m-d");

$seatStmt = $mysqli->prepare("
SELECT
    sb.booking_type,
    sb.booking_date,
    sb.booking_start,
    s.seat_no,
    s.seat_type,
    s.section_name,
    sub.start_date,
    sub.end_date,
    sub.status AS subscription_status
FROM seat_bookings sb
JOIN seats s ON s.id = sb.seat_id
LEFT JOIN subscriptions sub ON sub.id = sb.subscription_id
WHERE sb.user_id=?
AND sb.status='active'
AND (
    (sb.booking_type='daily' AND sb.booking_date=?)
    OR
    (sb.booking_type='fixed' AND sub.status='active' AND sub.end_date >= ?)
)
ORDER BY CASE WHEN sb.booking_type='fixed' THEN 0 ELSE 1 END, sb.id DESC
LIMIT 1
");
$seatStmt->bind_param("iss", $user_id, $today, $today);
$seatStmt->execute();
$seat = $seatStmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Seat</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
body{
min-height:100vh;
background:linear-gradient(120deg,#eef2ff,#e0f2fe);
padding:32px 18px;
color:#1e293b;
}
.page{
max-width:960px;
margin:0 auto;
}
.back-link{
display:inline-flex;
padding:10px 18px;
border-radius:999px;
background:#fff;
color:#2563eb;
text-decoration:none;
box-shadow:0 12px 25px rgba(37,99,235,0.12);
margin-bottom:22px;
}
.card{
background:#fff;
border-radius:28px;
padding:32px;
box-shadow:0 25px 50px rgba(15,23,42,0.1);
}
.card h1{
margin-bottom:10px;
}
.seat-banner{
margin:24px 0 28px;
padding:22px;
border-radius:22px;
background:linear-gradient(135deg,#2563eb,#1d4ed8);
color:#fff;
display:flex;
justify-content:space-between;
align-items:center;
gap:18px;
}
.seat-banner strong{
font-size:36px;
display:block;
}
.detail-grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
gap:16px;
}
.detail{
padding:18px;
border-radius:18px;
background:#f8fafc;
border:1px solid #e2e8f0;
}
.detail span{
display:block;
font-size:13px;
color:#64748b;
margin-bottom:6px;
}
.empty{
text-align:center;
padding:48px 24px;
border-radius:24px;
background:#fff;
box-shadow:0 25px 50px rgba(15,23,42,0.08);
}
.empty h1{
margin-bottom:10px;
}
.empty p{
color:#64748b;
margin-bottom:20px;
}
.primary-link{
display:inline-block;
padding:12px 22px;
border-radius:999px;
text-decoration:none;
background:linear-gradient(90deg,#2563eb,#4f46e5);
color:#fff;
}
</style>
</head>
<body>
<div class="page">
    <a class="back-link" href="dashboard.php">Back to Dashboard</a>

    <?php if ($seat): ?>
        <div class="card">
            <h1>My Current Seat</h1>

            <div class="seat-banner">
                <div>
                    <span>Seat Number</span>
                    <strong><?= htmlspecialchars($seat['seat_no']) ?></strong>
                </div>
                <div>
                    <?= $seat['booking_type'] === 'fixed' ? 'Fixed booking' : 'Today\'s daily booking' ?>
                </div>
            </div>

            <div class="detail-grid">
                <div class="detail">
                    <span>Seat Type</span>
                    <?= htmlspecialchars($seat['seat_type']) ?>
                </div>
                <div class="detail">
                    <span>Section</span>
                    <?= htmlspecialchars($seat['section_name']) ?>
                </div>
                <div class="detail">
                    <span>Booking Date</span>
                    <?= htmlspecialchars($seat['booking_date']) ?>
                </div>
                <div class="detail">
                    <span>Booking Start</span>
                    <?= htmlspecialchars($seat['booking_start']) ?>
                </div>
                <div class="detail">
                    <span>Plan Start</span>
                    <?= htmlspecialchars($seat['start_date'] ?? '-') ?>
                </div>
                <div class="detail">
                    <span>Plan End</span>
                    <?= htmlspecialchars($seat['end_date'] ?? '-') ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="empty">
            <h1>No Active Seat Yet</h1>
            <a class="primary-link" href="book-seat.php">Book a Seat</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
