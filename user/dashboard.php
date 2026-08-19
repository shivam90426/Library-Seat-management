<?php
require_once "../includes/security.php";
library_system_bootstrap();
require_once "../config/db.php";
require_once "../includes/diary_helpers.php";

require_login('user');

ensure_diary_entries_table($mysqli);

$user_id = intval($_SESSION['user_id']);
$today = date("Y-m-d");

$subStmt = $mysqli->prepare("
SELECT seat_type, end_date, start_date
FROM subscriptions
WHERE user_id=? AND status='active'
ORDER BY id DESC
LIMIT 1
");
$subStmt->bind_param("i", $user_id);
$subStmt->execute();
$sub = $subStmt->get_result()->fetch_assoc();

$bookingAllowed = $sub && $sub['end_date'] >= $today;
$entryAllowed = $bookingAllowed;
$graphLimit = $sub ? intval(str_replace("h", "", $sub['seat_type'])) : 6;
if ($graphLimit <= 0) {
    $graphLimit = 6;
}

$activeStmt = $mysqli->prepare("
SELECT entry_time
FROM timings
WHERE user_id=? AND exit_time IS NULL
LIMIT 1
");
$activeStmt->bind_param("i", $user_id);
$activeStmt->execute();
$activeRow = $activeStmt->get_result()->fetch_assoc();

$todayHoursStmt = $mysqli->prepare("
SELECT ROUND(IFNULL(SUM(GREATEST(duration_minutes, 0)), 0) / 60, 2) AS hours_today
FROM timings
WHERE user_id=? AND DATE(entry_time)=?
");
$todayHoursStmt->bind_param("is", $user_id, $today);
$todayHoursStmt->execute();
$todayHours = $todayHoursStmt->get_result()->fetch_assoc()['hours_today'] ?? 0;

$monthlyHoursStmt = $mysqli->prepare("
SELECT ROUND(IFNULL(SUM(GREATEST(duration_minutes, 0)), 0) / 60, 2) AS hours_month
FROM timings
WHERE user_id=? AND MONTH(entry_time)=MONTH(CURDATE()) AND YEAR(entry_time)=YEAR(CURDATE())
");
$monthlyHoursStmt->bind_param("i", $user_id);
$monthlyHoursStmt->execute();
$monthlyHours = $monthlyHoursStmt->get_result()->fetch_assoc()['hours_month'] ?? 0;

$seatStmt = $mysqli->prepare("
SELECT s.seat_no
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
$activeSeat = $seatStmt->get_result()->fetch_assoc();

$diaryStmt = $mysqli->prepare("
SELECT content
FROM diary_entries
WHERE user_id=? AND entry_date=?
LIMIT 1
");
$diaryStmt->bind_param("is", $user_id, $today);
$diaryStmt->execute();
$diaryEntry = $diaryStmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root{
--bg:#0f172a;
--bg-gradient:linear-gradient(135deg,#0f172a,#1e293b);
--text:#fff;
--muted:#cbd5e1;
--panel:rgba(255,255,255,0.06);
--panel-strong:rgba(255,255,255,0.08);
--panel-alt:rgba(15,23,42,0.5);
--border:rgba(255,255,255,0.08);
--shadow:0 20px 40px rgba(0,0,0,0.18);
--link:#fff;
}
body.light-mode{
--bg:#e2e8f0;
--bg-gradient:linear-gradient(135deg,#f8fafc,#dbeafe);
--text:#0f172a;
--muted:#475569;
--panel:rgba(255,255,255,0.88);
--panel-strong:rgba(255,255,255,0.96);
--panel-alt:rgba(226,232,240,0.9);
--border:rgba(15,23,42,0.08);
--shadow:0 20px 40px rgba(15,23,42,0.08);
--link:#0f172a;
}
body.glass-mode{
--bg:#07111d;
--bg-gradient:
radial-gradient(circle at top left, rgba(56,189,248,0.18), transparent 28%),
radial-gradient(circle at top right, rgba(236,72,153,0.14), transparent 24%),
linear-gradient(135deg,#07111d,#14263d);
--text:#f8fafc;
--muted:#cbd5e1;
--panel:rgba(255,255,255,0.08);
--panel-strong:rgba(255,255,255,0.14);
--panel-alt:rgba(255,255,255,0.1);
--border:rgba(255,255,255,0.18);
--shadow:0 20px 40px rgba(2,6,23,0.24);
--link:#f8fafc;
}
body.sunset-mode{
--bg:#2a1320;
--bg-gradient:linear-gradient(135deg,#2a1320,#4c1d3d,#7c2d12);
--text:#fff7ed;
--muted:#fed7aa;
--panel:rgba(255,247,237,0.08);
--panel-strong:rgba(255,247,237,0.14);
--panel-alt:rgba(124,45,18,0.24);
--border:rgba(255,237,213,0.18);
--shadow:0 20px 40px rgba(67,20,7,0.24);
--link:#fff7ed;
}
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
html{
min-height:100%;
background:var(--bg);
overscroll-behavior-y:none;
}
body{
min-height:100%;
overflow-x:hidden;
overflow-y:auto;
background:var(--bg-gradient);
background-attachment:fixed;
color:var(--text);
transition:background .25s ease,color .25s ease;
}
.dashboard{
display:grid;
grid-template-columns:240px 1fr;
min-height:100vh;
}
.sidebar{
position:sticky;
top:0;
align-self:start;
height:100vh;
padding:24px 18px;
background:linear-gradient(180deg,var(--panel-strong),var(--panel));
backdrop-filter:blur(12px);
border-right:1px solid var(--border);
display:flex;
flex-direction:column;
justify-content:space-between;
gap:24px;
box-shadow:18px 0 40px rgba(2,6,23,0.12);
}
.sidebar-top{
display:flex;
flex-direction:column;
gap:14px;
}
.brand-wrap{
padding:16px;
border-radius:20px;
background:linear-gradient(135deg,rgba(59,130,246,0.14),transparent);
border:1px solid var(--border);
}
.brand-sub{
font-size:12px;
letter-spacing:.08em;
text-transform:uppercase;
color:var(--muted);
margin-top:6px;
}
.sidebar-nav{
display:flex;
flex-direction:column;
gap:8px;
min-width:0;
}
.brand{
font-size:24px;
font-weight:600;
margin-bottom:0;
}
.sidebar a,
.mode-toggle{
display:block;
padding:12px 14px;
border-radius:14px;
color:var(--link);
text-decoration:none;
transition:.25s ease;
white-space:nowrap;
border:1px solid transparent;
background:transparent;
cursor:pointer;
font-size:14px;
text-align:left;
width:100%;
}
.sidebar a:hover,
.mode-toggle:hover{
background:var(--panel-strong);
transform:translateX(4px);
}
.sidebar a{
position:relative;
padding-left:16px;
}
.sidebar a::before{
content:"";
position:absolute;
left:0;
top:50%;
transform:translateY(-50%);
width:4px;
height:0;
border-radius:999px;
background:#38bdf8;
transition:.2s ease;
}
.sidebar a:hover::before{
height:60%;
}
.mode-toggle{
margin-bottom:8px;
background:linear-gradient(135deg,rgba(56,189,248,0.16),rgba(255,255,255,0.04));
border-color:rgba(56,189,248,0.24);
font-weight:600;
}
.mode-menu{
display:grid;
grid-template-columns:repeat(2,minmax(0,1fr));
gap:8px;
margin-bottom:8px;
}
.mode-option{
padding:10px 12px;
border-radius:12px;
border:1px solid var(--border);
background:var(--panel);
color:var(--link);
cursor:pointer;
font-size:13px;
font-weight:500;
transition:.2s ease;
}
.mode-option:hover,
.mode-option.active{
background:var(--panel-strong);
border-color:rgba(56,189,248,0.28);
}
.sidebar-footer{
padding-top:8px;
border-top:1px solid var(--border);
}
.main{
padding:24px;
display:flex;
flex-direction:column;
gap:18px;
min-height:0;
overflow:visible;
align-items:stretch;
}
.topbar{
display:flex;
justify-content:space-between;
align-items:center;
gap:18px;
flex-wrap:wrap;
padding:20px 22px;
border-radius:24px;
background:var(--panel);
border:1px solid var(--border);
}
.topbar p{
color:var(--muted);
margin-top:6px;
}
.timer-wrap{
display:flex;
align-items:center;
gap:12px;
flex-wrap:wrap;
}
.timer{
font-size:clamp(24px,4vw,30px);
font-weight:600;
min-width:96px;
}
.btn{
border:none;
padding:10px 16px;
border-radius:12px;
font-weight:600;
cursor:pointer;
}
.btn-green{
background:rgba(34,197,94,0.18);
border:1px solid #22c55e;
color:#86efac;
}
.btn-red{
background:rgba(239,68,68,0.18);
border:1px solid #ef4444;
color:#fca5a5;
}
body.light-mode .btn-green{
color:#166534;
}
body.light-mode .btn-red{
color:#991b1b;
}
.btn:disabled{
opacity:.45;
cursor:not-allowed;
}
.stats{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
gap:18px;
}
.card{
background:var(--panel);
border:1px solid var(--border);
border-radius:24px;
padding:20px;
box-shadow:var(--shadow);
min-width:0;
overflow:hidden;
display:flex;
flex-direction:column;
width:100%;
}
.card > *{
min-width:0;
}
.card span{
display:block;
font-size:14px;
color:var(--muted);
margin-bottom:8px;
}
.card strong{
font-size:28px;
}
.middle,
.bottom{
display:grid;
gap:18px;
min-height:0;
align-items:start;
grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
}
#weeklyChart{
width:100% !important;
height:260px !important;
display:block;
}
.chart-card{
min-height:320px;
justify-content:flex-start;
}
.calendar-card{
position:relative;
}
.calendar-head{
display:flex;
align-items:center;
justify-content:space-between;
gap:12px;
margin-bottom:8px;
padding:8px 10px;
border-radius:18px;
background:linear-gradient(135deg,rgba(59,130,246,0.16),rgba(255,255,255,0.03));
border:1px solid var(--border);
}
.calendar-title strong{
display:block;
font-size:14px;
font-weight:600;
}
.calendar-title span{
display:block;
font-size:11px;
color:var(--muted);
margin-top:2px;
}
.calendar-chip{
padding:5px 8px;
border-radius:999px;
background:var(--panel-strong);
border:1px solid var(--border);
font-size:10px;
font-weight:600;
color:var(--link);
}
.calendar-weekdays{
display:grid;
grid-template-columns:repeat(7,1fr);
gap:4px;
margin-bottom:4px;
}
.calendar-weekdays span{
text-align:center;
font-size:9px;
font-weight:600;
letter-spacing:.08em;
text-transform:uppercase;
color:var(--muted);
padding:2px 0;
}
#calendar{
display:grid;
grid-template-columns:repeat(7,1fr);
gap:4px;
min-width:0;
}
.calendar-empty{
border-radius:12px;
background:rgba(255,255,255,0.02);
border:1px dashed rgba(148,163,184,0.08);
min-height:26px;
}
.day{
position:relative;
padding:6px 0;
border-radius:10px;
text-align:center;
background:linear-gradient(180deg,var(--panel-strong),var(--panel));
cursor:pointer;
font-size:11px;
font-weight:600;
transition:.22s ease;
border:1px solid var(--border);
box-shadow:inset 0 1px 0 rgba(255,255,255,0.03);
}
.day:hover{
transform:translateY(-2px);
background:var(--panel-strong);
box-shadow:0 10px 20px rgba(2,6,23,0.08);
}
.day.disabled{
opacity:.35;
cursor:not-allowed;
}
.day.today{
background:linear-gradient(135deg,rgba(59,130,246,0.28),rgba(14,165,233,0.14));
border-color:rgba(56,189,248,0.38);
box-shadow:0 0 0 1px rgba(56,189,248,0.1), 0 10px 20px rgba(2,6,23,0.12);
}
.day.expiry{
background:rgba(239,68,68,0.22);
border:1px solid rgba(248,113,113,0.55);
}
#seatPreview{
height:100%;
min-height:150px;
border-radius:18px;
background:
linear-gradient(135deg, rgba(59,130,246,0.18), rgba(14,165,233,0.06)),
var(--panel-alt);
display:flex;
flex-direction:column;
justify-content:space-between;
align-items:flex-start;
cursor:pointer;
gap:14px;
padding:16px;
border:1px solid var(--border);
position:relative;
overflow:hidden;
}
#seatPreview::after{
content:"";
position:absolute;
right:-30px;
bottom:-30px;
width:100px;
height:100px;
border-radius:50%;
background:rgba(255,255,255,0.08);
}
.seat-preview-copy{
position:relative;
z-index:1;
}
.seat-preview-copy strong{
display:block;
font-size:20px;
margin-bottom:4px;
}
.seat-preview-copy p{
font-size:13px;
line-height:1.6;
color:var(--muted);
}
.seat-preview-meta{
display:flex;
align-items:center;
justify-content:space-between;
gap:12px;
width:100%;
position:relative;
z-index:1;
}
.seat-preview-badge{
display:inline-flex;
align-items:center;
padding:8px 12px;
border-radius:999px;
background:rgba(255,255,255,0.12);
border:1px solid var(--border);
font-size:12px;
font-weight:600;
}
.miniSeats{
display:flex;
gap:8px;
padding:8px 10px;
border-radius:999px;
background:rgba(255,255,255,0.08);
border:1px solid var(--border);
}
.miniSeats img{
width:20px;
}
.seat-legend{
display:flex;
flex-wrap:wrap;
gap:8px;
position:relative;
z-index:1;
}
.seat-legend span{
display:inline-flex;
align-items:center;
gap:6px;
padding:6px 10px;
border-radius:999px;
background:rgba(255,255,255,0.08);
border:1px solid var(--border);
font-size:11px;
color:var(--link);
}
.seat-legend i{
width:10px;
height:10px;
border-radius:50%;
display:inline-block;
}
.diary-card{
background:
linear-gradient(180deg, rgba(255,248,220,0.1), rgba(255,255,255,0.03)),
var(--panel);
}
.diary-head{
display:flex;
align-items:flex-start;
justify-content:space-between;
gap:12px;
margin-bottom:10px;
}
.diary-title strong{
display:block;
font-size:18px;
margin-bottom:4px;
}
.diary-title span{
font-size:12px;
color:var(--muted);
letter-spacing:.04em;
}
.diary-date{
padding:8px 12px;
border-radius:14px;
background:rgba(255,255,255,0.08);
border:1px solid var(--border);
font-size:11px;
font-weight:600;
color:var(--link);
white-space:nowrap;
}
.diary-box{
width:100%;
height:180px;
background:
repeating-linear-gradient(
to bottom,
rgba(255,255,255,0.03) 0,
rgba(255,255,255,0.03) 31px,
rgba(148,163,184,0.18) 31px,
rgba(148,163,184,0.18) 32px
),
linear-gradient(90deg, rgba(239,68,68,0.22) 0, rgba(239,68,68,0.22) 2px, transparent 2px),
var(--panel-alt);
background-position:left 0 top 0, 18px 0, 0 0;
background-repeat:repeat, no-repeat, no-repeat;
border:1px solid var(--border);
border-radius:18px;
color:var(--text);
padding:14px 14px 14px 28px;
resize:none;
outline:none;
margin:12px 0;
flex:0 0 auto;
line-height:32px;
font-size:14px;
font-family:"Georgia","Times New Roman",serif;
box-shadow:inset 0 1px 0 rgba(255,255,255,0.05);
}
.diary-box::placeholder{
color:var(--muted);
font-style:italic;
}
.diary-box:focus{
border-color:rgba(56,189,248,0.26);
box-shadow:0 0 0 3px rgba(56,189,248,0.08);
}
.actions{
display:flex;
justify-content:space-between;
align-items:center;
gap:12px;
flex-wrap:wrap;
}
.link-btn{
display:inline-flex;
padding:10px 16px;
border-radius:999px;
text-decoration:none;
color:var(--link);
background:var(--panel-strong);
border:1px solid var(--border);
white-space:nowrap;
}
.status-text{
font-size:13px;
color:var(--muted);
}
#seatPopup{
position:fixed;
inset:0;
display:none;
align-items:center;
justify-content:center;
background:rgba(2,6,23,0.78);
padding:20px;
z-index:999;
}
.popupCard{
width:min(1100px, 100%);
max-height:90vh;
overflow:auto;
background:var(--bg);
border-radius:28px;
padding:24px;
border:1px solid var(--border);
}
.popupHeader{
display:flex;
justify-content:space-between;
align-items:center;
gap:18px;
margin-bottom:20px;
}
.popupIntro{
padding:12px 14px;
margin-bottom:18px;
border-radius:16px;
background:var(--panel);
border:1px solid var(--border);
display:flex;
gap:10px;
flex-wrap:wrap;
}
.popupIntro span{
display:inline-flex;
align-items:center;
gap:6px;
padding:6px 10px;
border-radius:999px;
background:var(--panel-strong);
border:1px solid var(--border);
font-size:12px;
color:var(--link);
}
.popupIntro i{
width:10px;
height:10px;
border-radius:50%;
display:inline-block;
}
.popupHeader button{
border:none;
background:var(--panel-strong);
color:var(--text);
width:40px;
height:40px;
border-radius:50%;
cursor:pointer;
}
.seatMapGrid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
gap:18px;
}
.seatSection{
padding:18px;
border-radius:20px;
background:linear-gradient(180deg,var(--panel-strong),var(--panel));
border:1px solid var(--border);
box-shadow:var(--shadow);
}
.seatSection h4{
margin-bottom:12px;
font-size:15px;
}
.seatGrid{
display:grid;
grid-template-columns:repeat(auto-fill, minmax(42px, 42px));
gap:12px;
}
.seatNode{
width:42px;
height:42px;
display:flex;
align-items:center;
justify-content:center;
cursor:pointer;
flex-direction:column;
gap:2px;
border-radius:12px;
background:rgba(255,255,255,0.08);
border:1px solid transparent;
transition:.2s ease;
font-size:10px;
font-weight:600;
color:var(--muted);
}
.seatNode img{
width:18px;
}
.seatNode:hover:not(.disabled){
transform:translateY(-2px);
border-color:rgba(56,189,248,0.28);
background:rgba(255,255,255,0.12);
}
.seatNode.disabled{
opacity:.45;
cursor:not-allowed;
}
.seatNode.mine{
background:rgba(59,130,246,0.18);
border-color:rgba(59,130,246,0.36);
color:var(--link);
}
.seatNode.available{
background:rgba(34,197,94,0.14);
border-color:rgba(34,197,94,0.22);
}
.seatNode.booked{
background:rgba(239,68,68,0.14);
border-color:rgba(239,68,68,0.2);
}
.seatNode.blocked{
background:rgba(148,163,184,0.12);
border-color:rgba(148,163,184,0.18);
}
.seatLabel{
line-height:1;
}
@media(max-width:1080px){
.dashboard{
grid-template-columns:1fr;
}
.sidebar{
 position:relative;
 top:auto;
 align-self:stretch;
 height:auto;
 min-height:auto;
border-right:none;
border-bottom:1px solid var(--border);
padding-bottom:18px;
 box-shadow:none;
}
}
@media(max-width:768px){
body{
height:auto;
}
.sidebar{
padding:18px 16px;
}
.sidebar-nav{
flex-direction:column;
overflow:visible;
padding-bottom:0;
scrollbar-width:auto;
}
.mode-menu{
grid-template-columns:repeat(2,minmax(0,1fr));
}
.main{
padding:18px 16px 24px;
}
.topbar{
padding:18px;
align-items:flex-start;
}
.timer-wrap{
width:100%;
}
.stats{
grid-template-columns:repeat(2,minmax(0,1fr));
}
.middle,
.bottom{
grid-template-columns:1fr;
}
.card{
padding:18px;
border-radius:20px;
}
.popupCard{
padding:20px 16px;
border-radius:22px;
}
.popupHeader{
align-items:flex-start;
}
}
@media(max-width:560px){
.stats{
grid-template-columns:1fr;
}
.topbar h1{
font-size:26px;
}
.timer-wrap{
flex-direction:column;
align-items:stretch;
}
.btn,
.link-btn{
justify-content:center;
width:100%;
}
.mode-menu{
grid-template-columns:1fr;
}
#calendar{
gap:6px;
}
.calendar-head{
padding:8px 10px;
}
.calendar-chip{
padding:5px 8px;
font-size:10px;
}
.day{
padding:5px 0;
font-size:10px;
}
.diary-head{
flex-direction:column;
}
.diary-date{
width:100%;
text-align:center;
}
.actions{
align-items:stretch;
}
.seatMapGrid{
grid-template-columns:1fr;
}
.seat-preview-meta{
flex-direction:column;
align-items:flex-start;
}
.seat-preview-badge{
width:100%;
justify-content:center;
}
.miniSeats{
width:100%;
justify-content:center;
}
.seat-legend{
}
}
</style>
</head>
<body>
<div class="dashboard">
    <div class="sidebar">
        <div class="sidebar-top">
            <div class="brand-wrap">
                <div class="brand">Library</div>
                <div class="brand-sub">Focus dashboard</div>
            </div>
            <button class="mode-toggle" type="button" id="modeToggle">Themes</button>
            <div class="mode-menu" id="modeMenu">
                <button class="mode-option" type="button" data-mode="dark">Dark</button>
                <button class="mode-option" type="button" data-mode="light">Light</button>
                <button class="mode-option" type="button" data-mode="glass">Glass</button>
                <button class="mode-option" type="button" data-mode="sunset">Sunset</button>
            </div>
            <div class="sidebar-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="book-seat.php">Book Seat</a>
                <a href="my-seat.php">My Seat</a>
                <a href="subscription.php">Subscription</a>
                <a href="usage-analytics.php">Usage Analytics</a>
                <a href="diary.php">Diary</a>
            </div>
        </div>
        <div class="sidebar-footer">
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <div>
                <h1>Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Reader') ?></h1>
            </div>
            <div class="timer-wrap">
                <span class="timer" id="liveTimer">00:00</span>
                <button class="btn btn-green" id="entryBtn" <?= !$entryAllowed ? 'disabled' : '' ?>>Entry</button>
                <button class="btn btn-red" id="exitBtn" disabled>Exit</button>
            </div>
        </div>

        <div class="stats">
            <div class="card">
                <span>Today's Hours</span>
                <strong><?= htmlspecialchars($todayHours) ?></strong>
            </div>
            <div class="card">
                <span>This Month</span>
                <strong><?= htmlspecialchars($monthlyHours) ?></strong>
            </div>
            <div class="card">
                <span>Current Plan</span>
                <strong><?= htmlspecialchars($sub['seat_type'] ?? 'None') ?></strong>
            </div>
            <div class="card">
                <span>Active Seat</span>
                <strong><?= htmlspecialchars($activeSeat['seat_no'] ?? 'Not booked') ?></strong>
            </div>
        </div>

        <div class="middle">
            <div class="card">
                <h3 style="margin-bottom:12px;">Seat Booking</h3>
                <div id="seatPreview" onclick="openSeatPopup()">
                    <div class="seat-preview-copy">
                        <strong><?= htmlspecialchars($activeSeat['seat_no'] ?? 'Pick Your Seat') ?></strong>
                        <p>Open the seat map to review availability and reserve the best spot for your study session.</p>
                    </div>
                    <div class="seat-preview-meta">
                        <div class="miniSeats">
                            <img src="../assets/seats/green.png" alt="Available seat">
                            <img src="../assets/seats/blue.png" alt="Your seat">
                            <img src="../assets/seats/red.png" alt="Booked seat">
                        </div>
                        <span class="seat-preview-badge">Tap to Open Map</span>
                    </div>
                    <div class="seat-legend">
                        <span><i style="background:#22c55e;"></i> Available</span>
                        <span><i style="background:#3b82f6;"></i> Yours</span>
                        <span><i style="background:#ef4444;"></i> Booked</span>
                    </div>
                </div>
            </div>

            <div class="card calendar-card">
                <div class="calendar-head">
                    <div class="calendar-title">
                        <strong>Calendar</strong>
                        <span id="calendarMonthLabel">This month overview</span>
                    </div>
                    <div class="calendar-chip">Study Days</div>
                </div>
                <div class="calendar-weekdays">
                    <span>Mon</span>
                    <span>Tue</span>
                    <span>Wed</span>
                    <span>Thu</span>
                    <span>Fri</span>
                    <span>Sat</span>
                    <span>Sun</span>
                </div>
                <div id="calendar"></div>
            </div>
        </div>

        <div class="bottom">
            <div class="card chart-card">
                <h3 style="margin-bottom:12px;">Weekly Usage</h3>
                <canvas id="weeklyChart"></canvas>
            </div>

            <div class="card diary-card">
                <div class="diary-head">
                    <div class="diary-title">
                        <strong>Today's Diary</strong>
                    </div>
                    <div class="diary-date"><?= htmlspecialchars(date("d M Y")) ?></div>
                </div>
                <textarea class="diary-box" id="diaryBox" placeholder="Write a few notes about today."><?= htmlspecialchars($diaryEntry['content'] ?? '') ?></textarea>
                <div class="actions">
                    <button class="btn btn-green" id="saveDiaryBtn">Save Note</button>
                    <span class="status-text" id="diaryStatus"></span>
                </div>
            </div>

            <div class="card">
                <h3 style="margin-bottom:14px;">Quick Actions</h3>
                <div class="actions" style="justify-content:flex-start;">
                    <a class="link-btn" href="my-seat.php">View My Seat</a>
                    <a class="link-btn" href="usage-analytics.php">Open Analytics</a>
                    <a class="link-btn" href="diary.php">Open Diary</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="seatPopup">
    <div class="popupCard">
        <div class="popupHeader">
            <div>
                <h2>Seat Map</h2>
                <p style="color:var(--muted);margin-top:6px;">Blue is yours, green is available, red is booked, and gray is blocked.</p>
            </div>
            <button type="button" onclick="closeSeatPopup()">X</button>
        </div>
        <div class="popupIntro">
            <span><i style="background:#22c55e;"></i> Available</span>
            <span><i style="background:#3b82f6;"></i> My Seat</span>
            <span><i style="background:#ef4444;"></i> Booked</span>
            <span><i style="background:#94a3b8;"></i> Blocked</span>
        </div>
        <div class="seatMapGrid" id="seatMapGrid"></div>
    </div>
</div>

<script>
const csrfToken = "<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>";
const entryBtn = document.getElementById("entryBtn");
const exitBtn = document.getElementById("exitBtn");
const liveTimer = document.getElementById("liveTimer");
const diaryBox = document.getElementById("diaryBox");
const saveDiaryBtn = document.getElementById("saveDiaryBtn");
const diaryStatus = document.getElementById("diaryStatus");
const calendar = document.getElementById("calendar");
const calendarMonthLabel = document.getElementById("calendarMonthLabel");
const seatMapGrid = document.getElementById("seatMapGrid");
const modeToggle = document.getElementById("modeToggle");
const modeMenu = document.getElementById("modeMenu");
const modeOptions = document.querySelectorAll(".mode-option");
const expiryDate = "<?= $sub['end_date'] ?? '' ?>";
const graphLimit = <?= $graphLimit ?>;
let entryTime = <?= $activeRow ? '"' . $activeRow['entry_time'] . '"' : 'null' ?>;

function applyMode(mode) {
    document.body.classList.remove("light-mode", "glass-mode", "sunset-mode");
    if (mode === "light") {
        document.body.classList.add("light-mode");
    } else if (mode === "glass") {
        document.body.classList.add("glass-mode");
    } else if (mode === "sunset") {
        document.body.classList.add("sunset-mode");
    }
    modeOptions.forEach(option => {
        option.classList.toggle("active", option.dataset.mode === mode);
    });
    modeToggle.textContent = "Theme: " + mode.charAt(0).toUpperCase() + mode.slice(1);
    localStorage.setItem("dashboardMode", mode);
}

const savedMode = localStorage.getItem("dashboardMode") || "dark";
applyMode(savedMode);

modeToggle.addEventListener("click", function () {
    modeMenu.scrollIntoView({ block: "nearest", behavior: "smooth" });
});

modeOptions.forEach(option => {
    option.addEventListener("click", function () {
        applyMode(option.dataset.mode);
    });
});

function startTimer(startTime) {
    function update() {
        const now = new Date().getTime();
        const start = new Date(startTime).getTime();
        const diff = now - start;
        let minutes = Math.floor(diff / (1000 * 60));
        const hours = Math.floor(minutes / 60);
        minutes = minutes % 60;
        liveTimer.textContent = String(hours).padStart(2, "0") + ":" + String(minutes).padStart(2, "0");
    }

    update();
    window.timerInterval = setInterval(update, 60000);
}

if (entryTime) {
    startTimer(entryTime);
    entryBtn.disabled = true;
    exitBtn.disabled = false;
}

entryBtn.addEventListener("click", function () {
    fetch("api/timer-entry.php", {
        method: "POST",
        headers: {
            "X-CSRF-Token": csrfToken
        }
    })
        .then(res => res.text())
        .then(data => {
            if (data === "started") {
                location.reload();
                return;
            }
            if (data === "expired") {
                alert("Your subscription has expired.");
                return;
            }
            if (data === "already") {
                alert("You already have an active session.");
            }
        });
});

exitBtn.addEventListener("click", function () {
    fetch("api/timer-exit.php", {
        method: "POST",
        headers: {
            "X-CSRF-Token": csrfToken
        }
    })
        .then(res => res.text())
        .then(data => {
            if (data === "stopped") {
                location.reload();
            }
        });
});

fetch("api/get-weekly.php")
    .then(res => res.json())
    .then(data => {
        new Chart(document.getElementById("weeklyChart"), {
            type: "bar",
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.hours,
                    backgroundColor: [
                        "#3b82f6",
                        "#8b5cf6",
                        "#06b6d4",
                        "#22c55e",
                        "#f59e0b",
                        "#f97316",
                        "#ec4899"
                    ],
                    borderRadius: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: graphLimit,
                        ticks: { color: "#94a3b8" },
                        grid: { color: "rgba(148,163,184,0.18)" }
                    },
                    x: {
                        ticks: { color: "#94a3b8" },
                        grid: { display: false }
                    }
                }
            }
        });
    });

function generateCalendar() {
    const todayObj = new Date();
    const year = todayObj.getFullYear();
    const month = todayObj.getMonth();
    const firstDay = new Date(year, month, 1);
    let startDay = firstDay.getDay();

    startDay = startDay === 0 ? 6 : startDay - 1;

    const daysInMonth = new Date(year, month + 1, 0).getDate();
    calendar.innerHTML = "";
    calendarMonthLabel.textContent = todayObj.toLocaleDateString("en-US", {
        month: "long",
        year: "numeric"
    });

    for (let i = 0; i < startDay; i++) {
        calendar.innerHTML += "<div class=\"calendar-empty\"></div>";
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const fullDate = `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
        const div = document.createElement("div");
        div.className = "day";
        div.textContent = day;

        if (day === todayObj.getDate()) {
            div.classList.add("today");
        }

        if (fullDate === expiryDate) {
            div.classList.add("expiry");
        }

        if (new Date(fullDate) > todayObj) {
            div.classList.add("disabled");
        } else {
            div.onclick = function () {
                fetch("api/get-date-hours.php?date=" + encodeURIComponent(fullDate))
                    .then(res => res.json())
                    .then(data => {
                        alert(fullDate + " : " + data.hours + " hrs");
                    });
            };
        }

        calendar.appendChild(div);
    }
}

function seatImage(status) {
    if (status === "mine") return "../assets/seats/blue.png";
    if (status === "booked") return "../assets/seats/red.png";
    if (status === "blocked") return "../assets/seats/gray.svg";
    return "../assets/seats/green.png";
}

function openSeatPopup() {
    document.getElementById("seatPopup").style.display = "flex";
    loadSeatMap();
}

function closeSeatPopup() {
    document.getElementById("seatPopup").style.display = "none";
}

function loadSeatMap() {
    fetch("api/get-seats-map.php")
        .then(res => res.json())
        .then(data => {
            const grouped = {};
            seatMapGrid.innerHTML = "";

            data.forEach(seat => {
                const section = seat.section || "General";
                if (!grouped[section]) {
                    grouped[section] = [];
                }
                grouped[section].push(seat);
            });

            Object.keys(grouped).forEach(sectionName => {
                const section = document.createElement("div");
                section.className = "seatSection";

                const title = document.createElement("h4");
                title.textContent = sectionName.replace(/_/g, " ").replace(/\b\w/g, letter => letter.toUpperCase());
                section.appendChild(title);

                const grid = document.createElement("div");
                grid.className = "seatGrid";

                grouped[sectionName].forEach(seat => {
                    const seatNode = document.createElement("div");
                    seatNode.className = "seatNode";
                    seatNode.classList.add(seat.status);

                    if (seat.status === "booked" || seat.status === "blocked") {
                        seatNode.classList.add("disabled");
                    }

                    const image = document.createElement("img");
                    image.src = seatImage(seat.status);
                    image.alt = seat.seat_no;
                    image.title = seat.seat_no + " (" + seat.seat_type + ")";
                    seatNode.appendChild(image);

                    const label = document.createElement("span");
                    label.className = "seatLabel";
                    label.textContent = seat.seat_no;
                    seatNode.appendChild(label);

                    if (seat.status === "available") {
                        seatNode.onclick = function () {
                            bookSeat(seat.id);
                        };
                    } else if (seat.status !== "mine") {
                        seatNode.classList.add("disabled");
                    }

                    grid.appendChild(seatNode);
                });

                section.appendChild(grid);
                seatMapGrid.appendChild(section);
            });
        });
}

function bookSeat(seatId) {
    fetch("api/book-seat.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-CSRF-Token": csrfToken
        },
        body: "seat_id=" + encodeURIComponent(seatId)
    })
    .then(res => res.text())
    .then(response => {
        if (response === "expired") {
            alert("Your subscription has expired.");
            return;
        }
        if (response === "already") {
            alert("You already have an active seat booking.");
            return;
        }
        if (response === "taken") {
            alert("That seat is already booked.");
            return;
        }
        if (response === "booked") {
            alert("Seat booked successfully.");
            loadSeatMap();
            return;
        }
        alert("Unable to book this seat right now.");
    });
}

saveDiaryBtn.addEventListener("click", function () {
    diaryStatus.textContent = "Saving...";

    fetch("api/save-diary-entry.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-CSRF-Token": csrfToken
        },
        body: "date=<?= $today ?>&content=" + encodeURIComponent(diaryBox.value)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status !== "success") {
            diaryStatus.textContent = data.message || "Unable to save right now.";
            return;
        }
        diaryStatus.textContent = "Saved at " + data.updated_at;
    })
    .catch(() => {
        diaryStatus.textContent = "Unable to save right now.";
    });
});

generateCalendar();
</script>
</body>
</html>
