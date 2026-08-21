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
    sb.booking_end,
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

/* =========================================================
   GLASS UI — linked user pages
   Visual-only overrides: existing PHP/JS functionality kept.
   ========================================================= */
:root{
  --g-bg:#06111f;
  --g-bg2:#0a1b2d;
  --g-card:rgba(13,31,50,.68);
  --g-card2:rgba(18,42,66,.56);
  --g-border:rgba(165,205,245,.20);
  --g-border-hi:rgba(130,170,255,.34);
  --g-text:#f7fbff;
  --g-muted:#b9c9dc;
  --g-purple:#8b5cf6;
  --g-blue:#3b82f6;
  --g-green:#22c55e;
  --g-red:#ef4444;
  --g-orange:#f59e0b;
}
html{background:var(--g-bg)!important;}
body{
  background:
    radial-gradient(circle at 8% 0%,rgba(56,189,248,.14),transparent 28%),
    radial-gradient(circle at 92% 4%,rgba(139,92,246,.14),transparent 28%),
    linear-gradient(135deg,var(--g-bg),var(--g-bg2))!important;
  color:var(--g-text)!important;
  min-height:100vh;
}
.page{position:relative;}
a{color:var(--g-text);}
.back-link,.back,.logout,.back-btn,.action-link{
  background:rgba(255,255,255,.07)!important;
  border:1px solid var(--g-border)!important;
  color:var(--g-text)!important;
  box-shadow:0 10px 30px rgba(0,0,0,.16),inset 0 1px rgba(255,255,255,.08)!important;
  backdrop-filter:blur(14px);
}
.back-link:hover,.back:hover,.logout:hover,.back-btn:hover,.action-link:hover{
  border-color:var(--g-border-hi)!important;
  background:rgba(255,255,255,.11)!important;
}
.card,.panel,.hero,.chart-card,.empty{
  background:linear-gradient(135deg,rgba(15,36,58,.76),rgba(7,22,38,.64))!important;
  border:1px solid var(--g-border)!important;
  box-shadow:0 22px 55px rgba(0,0,0,.24),inset 0 1px rgba(255,255,255,.055)!important;
  backdrop-filter:blur(18px)!important;
}
h1,h2,h3,h4,strong{color:var(--g-text);}
p,.meta,.help,#statusText,.features,.note,.legend-item,.detail span,.card span{color:var(--g-muted)!important;}
.status{
  background:rgba(59,130,246,.13)!important;
  border-color:rgba(96,165,250,.35)!important;
}
.status.expired{
  background:rgba(239,68,68,.13)!important;
  border-color:rgba(248,113,113,.38)!important;
}
input,textarea,select{
  background:rgba(3,15,28,.62)!important;
  color:var(--g-text)!important;
  border:1px solid var(--g-border)!important;
}
input::placeholder,textarea::placeholder{color:#7f94ab!important;}
input:focus,textarea:focus,select:focus{
  border-color:rgba(139,92,246,.65)!important;
  box-shadow:0 0 0 3px rgba(139,92,246,.13)!important;
}
button,.button,.primary-link{
  background:linear-gradient(135deg,#4f46e5,#7c3aed)!important;
  color:#fff!important;
  border:1px solid rgba(167,139,250,.34)!important;
  box-shadow:0 10px 24px rgba(79,70,229,.22)!important;
}
button:hover,.button:hover,.primary-link:hover{filter:brightness(1.08);}
.seat{
  background:rgba(255,255,255,.045)!important;
  border:1px solid rgba(255,255,255,.08);
}
.seat:hover{
  box-shadow:0 10px 24px rgba(59,130,246,.25)!important;
}
.note{
  background:rgba(3,15,28,.48)!important;
  border:1px solid rgba(255,255,255,.08);
}
.shift-modal{background:rgba(1,8,18,.78)!important;backdrop-filter:blur(7px);}
.shift-card,.shift-option{
  background:rgba(10,29,48,.88)!important;
  border-color:var(--g-border)!important;
  color:var(--g-text)!important;
  backdrop-filter:blur(18px);
}
.shift-option span{color:var(--g-muted)!important;}
.shift-close{
  background:rgba(255,255,255,.06)!important;
  border-color:var(--g-border)!important;
  color:var(--g-text)!important;
}

/* My Seat */
.seat-banner{
  background:linear-gradient(135deg,rgba(59,130,246,.24),rgba(99,102,241,.16))!important;
  border:1px solid rgba(96,165,250,.25);
  box-shadow:inset 0 1px rgba(255,255,255,.08);
}
.detail{
  background:rgba(255,255,255,.045)!important;
  border-color:var(--g-border)!important;
}

/* Subscription */
.grid{gap:20px!important;}
.card .price,.price,.plan{color:#a78bfa!important;}
.warning{
  background:rgba(239,68,68,.12)!important;
  border:1px solid rgba(248,113,113,.28)!important;
  color:#fca5a5!important;
}

/* Profile */
.avatar{
  border:1px solid rgba(139,92,246,.55)!important;
  box-shadow:0 12px 35px rgba(79,70,229,.25)!important;
}
.header{border-bottom-color:var(--g-border)!important;}
.message.success{
  background:rgba(34,197,94,.11)!important;
  border-color:rgba(34,197,94,.28)!important;
  color:#86efac!important;
}
.message.error{
  background:rgba(239,68,68,.11)!important;
  border-color:rgba(239,68,68,.28)!important;
  color:#fca5a5!important;
}
.note{color:var(--g-muted)!important;}

/* Payment */
.qr-box img{
  border:1px solid var(--g-border)!important;
  box-shadow:0 12px 30px rgba(0,0,0,.25)!important;
}
.upi-box{
  background:rgba(255,255,255,.055)!important;
  border:1px solid var(--g-border)!important;
}
.success{
  background:rgba(34,197,94,.11)!important;
  border:1px solid rgba(34,197,94,.25)!important;
  color:#86efac!important;
}

/* Tables / history */
table{
  background:rgba(11,29,47,.72)!important;
  color:var(--g-text)!important;
  border:1px solid var(--g-border)!important;
  box-shadow:0 22px 50px rgba(0,0,0,.25)!important;
}
th{
  background:linear-gradient(135deg,#3730a3,#4f46e5)!important;
}
tr:nth-child(even){background:rgba(255,255,255,.035)!important;}
tr:hover{background:rgba(255,255,255,.06)!important;}
td{border-bottom:1px solid rgba(255,255,255,.06);}
.status-approved{color:#4ade80!important;}
.status-pending{color:#fbbf24!important;}
.status-rejected{color:#f87171!important;}

/* Analytics */
.stats .card strong{color:var(--g-text)!important;}
canvas{filter:drop-shadow(0 8px 18px rgba(59,130,246,.08));}

/* Small screens */
@media(max-width:700px){
  body{padding:20px 12px!important;}
  .card,.panel,.hero{border-radius:20px!important;}
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
                    <span>Booking End</span>
                    <?= htmlspecialchars($seat['booking_end']) ?>
                </div>
                <?php if ($seat['seat_type'] === '6h'): ?>
                    <div class="detail">
                        <span>Shift</span>
                        <?= date('H:i', strtotime($seat['booking_start'])) === '06:00' ? '🌅 Morning (6:00 AM – 12:00 PM)' : '🌆 Evening (12:00 PM – 6:00 PM)' ?>
                    </div>
                <?php endif; ?>
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
