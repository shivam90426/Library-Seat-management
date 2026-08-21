<?php
require_once "../includes/security.php";
library_system_bootstrap();
require_once "../config/db.php";
require_once "../includes/diary_helpers.php";

require_login('user');

ensure_diary_entries_table($mysqli);

$user_id = intval($_SESSION['user_id']);
$today = date("Y-m-d");

$userStmt = $mysqli->prepare("
SELECT name, email, phone, profile_pic
FROM users
WHERE id=?
LIMIT 1
");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$currentUser = $userStmt->get_result()->fetch_assoc() ?: [];
$userName = $currentUser['name'] ?? ($_SESSION['name'] ?? 'Reader');
$userProfilePic = trim((string)($currentUser['profile_pic'] ?? ''));
$userInitial = strtoupper(substr(trim($userName), 0, 1));

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
max-height:100vh;
padding:24px 18px;
overflow-y:auto;
scrollbar-width:thin;
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
/* Logout button */
.sidebar-footer{
    position:sticky;
    bottom:0;
    z-index:5;
    padding:8px 10px 12px;
    border-top:0;
    background:transparent;
    backdrop-filter:none;
}
.sidebar-footer a{
    display:flex;
    align-items:center;
    justify-content:center;
    width:100%;
    margin:0;
    padding:11px 14px;
    border-radius:12px;
    color:#fecaca;
    background:rgba(239,68,68,0.07);
    border:1px solid rgba(239,68,68,0.20);
    text-align:center;
    font-weight:500;
    transition:all .25s ease;
}
.sidebar-footer a::before{display:none;}
.sidebar-footer a:hover{
    background:rgba(239,68,68,0.12);
    border-color:rgba(248,113,113,0.34);
    color:#fee2e2;
    transform:translateY(-1px);
    box-shadow:0 8px 20px rgba(239,68,68,0.10);
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
.welcome-wrap{
display:flex;
align-items:center;
gap:14px;
min-width:0;
}
.profile-avatar-link{
width:56px;
height:56px;
border-radius:18px;
flex:0 0 56px;
display:flex;
align-items:center;
justify-content:center;
overflow:hidden;
text-decoration:none;
background:linear-gradient(135deg,#2563eb,#7c3aed);
border:1px solid var(--border);
box-shadow:0 10px 25px rgba(37,99,235,.22);
transition:transform .2s ease, box-shadow .2s ease;
}
.profile-avatar-link:hover{
transform:translateY(-2px) scale(1.03);
box-shadow:0 14px 30px rgba(37,99,235,.30);
}
.profile-avatar-link img{
width:100%;
height:100%;
object-fit:cover;
display:block;
}
.profile-avatar-fallback{
font-size:21px;
font-weight:700;
color:#fff;
}
.profile-mini-link{
font-size:12px;
color:var(--muted);
text-decoration:none;
margin-top:3px;
display:inline-block;
}
.profile-mini-link:hover{color:var(--link);}

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
.shiftModal{
position:fixed;inset:0;display:none;align-items:center;justify-content:center;
background:rgba(2,6,23,.82);padding:20px;z-index:1200;
}
.shiftCard{width:min(520px,100%);background:var(--bg);border:1px solid var(--border);border-radius:24px;padding:24px;box-shadow:var(--shadow);}
.shiftOptions{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:20px;}
.shiftOption{border:1px solid var(--border);background:var(--panel);color:var(--text);border-radius:18px;padding:18px;text-align:left;cursor:pointer;transition:.2s;}
.shiftOption:hover:not(:disabled){transform:translateY(-2px);border-color:var(--link);}
.shiftOption:disabled{opacity:.45;cursor:not-allowed;}
.shiftOption strong{display:block;font-size:16px;margin-bottom:5px;}
.shiftOption span{display:block;color:var(--muted);font-size:13px;}
.shiftOption .shiftState{margin-top:10px;color:#22c55e;font-size:12px;}
.shiftOption:disabled .shiftState{color:#ef4444;}
.shiftCancel{width:100%;margin-top:16px;padding:11px;border-radius:999px;border:1px solid var(--border);background:transparent;color:var(--text);cursor:pointer;}
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

/* ===== TARGET DASHBOARD UI OVERRIDES ===== */
:root{
  --bg:#020b18;
  --text:#f8fafc;
  --muted:#aebbd0;
  --panel:rgba(5,18,34,.82);
  --panel-strong:rgba(11,28,48,.92);
  --panel-alt:#0a1a30;
  --border:rgba(135,166,207,.16);
  --link:#f8fafc;
  --shadow:0 18px 45px rgba(0,0,0,.28);
}
body{background:
  radial-gradient(circle at 82% 8%,rgba(103,45,255,.10),transparent 25%),
  radial-gradient(circle at 42% 100%,rgba(0,116,255,.08),transparent 30%),
  #020b18;
}
.dashboard{grid-template-columns:220px minmax(0,1fr);min-height:100vh;}
.sidebar{
  width:220px;height:100vh;position:sticky;top:0;
  padding:10px 12px 12px;background:linear-gradient(180deg,#061426 0%,#03101e 100%);
  border-right:1px solid rgba(145,176,214,.16);box-shadow:none;gap:12px;
}
.sidebar-top{gap:10px;}
.brand-wrap{
  padding:10px 10px 12px;text-align:center;border-radius:20px;
  background:linear-gradient(180deg,rgba(20,48,78,.42),rgba(4,18,33,.34));
  border:1px solid rgba(127,164,208,.18);
}
.brand-mark{width:82px;height:64px;object-fit:cover;display:block;margin:0 auto 2px;border-radius:10px;}
.brand{font-size:28px;line-height:1.05;font-weight:600;letter-spacing:-.02em;}
.brand-sub{font-size:12px;letter-spacing:.08em;margin-top:7px;color:#aebbd0;}
.sidebar-nav{gap:4px;order:0;}
.sidebar a,.mode-toggle{
  padding:10px 12px;border-radius:12px;font-size:14px;color:#edf4ff;
}
.sidebar a{display:flex;align-items:center;gap:12px;padding-left:14px;}
.sidebar a::before{display:none;}
.sidebar a.active,.sidebar a:hover{
  background:linear-gradient(90deg,rgba(75,58,255,.45),rgba(28,91,178,.22));
  border:1px solid rgba(100,91,255,.55);transform:none;
}
.nav-icon,.stat-icon,.action-icon{width:23px;height:23px;display:inline-flex;align-items:center;justify-content:center;flex:0 0 23px;}
.nav-icon svg,.stat-icon svg,.action-icon svg{width:100%;height:100%;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.theme-area{margin-top:2px;padding:8px 5px 0;border-top:1px solid rgba(145,176,214,.10);}
.theme-title{font-size:12px;color:#9fb0c7;letter-spacing:.06em;margin:0 8px 8px;text-transform:uppercase;}
.mode-toggle{
  margin:0 0 7px;width:100%;text-align:left;background:rgba(11,28,48,.72);
  border-color:rgba(135,166,207,.20);font-weight:500;
}
.mode-menu{gap:6px;margin:0;}
.mode-option{padding:9px 8px;border-radius:11px;background:rgba(10,26,47,.65);font-size:12px;}
.mode-option.active{background:linear-gradient(135deg,rgba(80,62,255,.34),rgba(31,94,176,.22));border-color:#695cff;}
.sidebar-footer{padding-top:9px;border-top:1px solid rgba(145,176,214,.10);}
.sidebar-footer a{
  justify-content:center;color:#ff9c9c;border:1px solid rgba(255,70,70,.6);
  background:rgba(255,45,45,.035);height:46px;
}
.main{padding:14px 22px 20px;gap:16px;overflow:visible;}
.topbar{
  min-height:156px;padding:20px 24px;border-radius:23px;
  background:linear-gradient(135deg,rgba(6,22,39,.96),rgba(5,16,31,.92));
  border:1px solid rgba(125,159,201,.16);box-shadow:0 15px 35px rgba(0,0,0,.20);
  flex-wrap:nowrap;
}
.welcome-wrap{gap:16px;}
.profile-avatar-link{
  width:84px;height:84px;flex:0 0 84px;border-radius:50%;
  border:2px solid #6c5cff;background:#0a1a30;box-shadow:0 0 0 4px rgba(92,76,255,.12),0 0 28px rgba(93,72,255,.25);
}
.profile-avatar-link img{border-radius:50%;}
.profile-avatar-fallback{font-size:28px;}
.topbar h1{font-size:32px;line-height:1.1;letter-spacing:-.03em;}
.profile-mini-link{font-size:13px;color:#a9a5ff;margin-top:8px;}
.timer-wrap{margin-left:auto;gap:24px;flex-wrap:nowrap;}
.timer-box{
  width:290px;height:118px;border-radius:19px;position:relative;display:flex;align-items:center;
  justify-content:center;overflow:hidden;background:#071326;border:1px solid rgba(135,166,207,.24);
  box-shadow:inset 0 0 35px rgba(0,0,0,.25);
}
.timer-box::before{
  content:"";position:absolute;inset:-3px;border-radius:22px;padding:3px;
  background:conic-gradient(from 0deg,transparent 0 72%,#9b46ff 77%,#6d4cff 84%,#2398ff 90%,transparent 96%);
  -webkit-mask:linear-gradient(#000 0 0) content-box,linear-gradient(#000 0 0);
  -webkit-mask-composite:xor;mask-composite:exclude;animation:timerSnake 3.5s linear infinite;
  pointer-events:none;
}
.timer-box::after{
  content:"";position:absolute;inset:2px;border-radius:17px;
  border:1px solid rgba(110,138,185,.12);pointer-events:none;
}
@keyframes timerSnake{to{transform:rotate(360deg)}}
.timer-content{position:relative;z-index:1;text-align:left;min-width:180px;}
.timer-line{display:flex;align-items:center;gap:12px;}
.timer-clock-icon{width:28px;height:28px;color:#82a4ff;}
.timer-clock-icon svg{width:100%;height:100%;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.timer{font-size:31px;font-weight:500;line-height:1;color:#fff;min-width:auto;letter-spacing:.02em;}
.timer-sub{font-size:13px;color:#b8c3d5;margin:10px 0 0 40px;}
.timer-sub .moon{color:#fff;margin-right:5px;}
.btn{padding:13px 24px;border-radius:13px;font-size:15px;min-width:128px;background:transparent;}
.btn-green{color:#42e38a;background:rgba(19,102,63,.10);border:1px solid #16a05a;}
.btn-red{color:#ff5555;background:rgba(126,27,27,.08);border:1px solid #ff3b3b;}
.stats{grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;}
.stats .card{
  height:120px;padding:20px;border-radius:20px;position:relative;display:grid;
  grid-template-columns:80px 1fr;grid-template-rows:1fr 1fr;column-gap:14px;
  background:linear-gradient(135deg,rgba(5,20,37,.94),rgba(4,15,29,.88));
}
.stat-icon{
  grid-row:1 / span 2;width:76px;height:76px;border-radius:50%;
  align-self:center;border:1px solid currentColor;background:rgba(8,24,42,.58);
}
.stat-copy span{font-size:14px;margin:2px 0 2px;color:#b9c7db;}
.stat-copy strong{font-size:29px;line-height:1.05;}
.stat-unit{font-size:13px;color:#b7c3d5;margin-top:2px;}
.stat-purple{color:#8b5cf6}.stat-blue{color:#4b9cff}.stat-green{color:#28d66f}.stat-orange{color:#ff9a00}
.middle{grid-template-columns:1.03fr .97fr;gap:16px;}
.middle>.card,.bottom>.card{border-radius:20px;background:linear-gradient(135deg,rgba(5,20,37,.94),rgba(4,15,29,.90));border-color:rgba(125,159,201,.15);}
.middle>.card{min-height:350px;padding:22px 24px;}
.middle h3,.bottom h3{font-size:23px;}
#seatPreview{
  min-height:258px;margin-top:10px;padding:26px 28px;justify-content:space-between;
  background:linear-gradient(135deg,rgba(8,29,53,.96),rgba(5,17,32,.88));
  border-color:rgba(90,126,173,.28);border-radius:18px;
}
.seat-preview-copy strong{font-size:25px;}
.seat-preview-copy p{font-size:15px;max-width:620px;line-height:1.65;}
.seat-preview-meta{margin-top:auto;}
.miniSeats{gap:5px;padding:6px 9px;background:rgba(255,255,255,.05);}
.miniSeats img{width:28px;height:28px;}
.seat-preview-badge{font-size:15px;padding:12px 20px;border-radius:20px;background:rgba(86,78,255,.24);border-color:rgba(107,91,255,.6);}
.seat-legend{padding-top:10px;border-top:1px solid rgba(135,166,207,.12);width:100%;gap:24px;}
.seat-legend span{background:transparent;border:none;padding:0;font-size:14px;}
.calendar-card{padding:20px 24px!important;}
.calendar-head{background:transparent;border:none;padding:0 0 14px;margin:0;}
.calendar-title strong{font-size:23px;}
.calendar-title span{font-size:15px;margin-top:4px;}
.calendar-chip{padding:9px 16px;font-size:13px;border-radius:22px;background:rgba(8,24,42,.7);}
.calendar-weekdays{gap:7px;margin-bottom:7px;}
.calendar-weekdays span{font-size:11px;}
#calendar{gap:7px;}
.calendar-empty{min-height:31px;border:none;background:transparent;}
.day{padding:8px 0;min-height:36px;border-radius:10px;font-size:13px;background:rgba(7,22,39,.62);}
.day.today{background:rgba(59,71,214,.55);border-color:#356fff;box-shadow:none;}
.bottom{grid-template-columns:1fr 1fr 1fr;gap:16px;}
.bottom>.card{min-height:245px;padding:20px 24px;}
.chart-card{min-height:245px;}
#weeklyChart{height:160px!important;}
.diary-card{background:linear-gradient(135deg,rgba(5,20,37,.94),rgba(4,15,29,.90));}
.diary-head{margin-bottom:8px;}
.diary-title strong{font-size:23px;}
.diary-date{padding:8px 14px;border-radius:20px;background:rgba(8,24,42,.7);font-size:12px;}
.diary-box{height:138px;margin:6px 0 0;border-radius:16px;background:#071326;border-color:#7944c8;}
.actions{gap:10px;}
.link-btn{flex:1;justify-content:center;padding:12px 14px;border-radius:13px;background:rgba(8,24,42,.75);border-color:rgba(115,143,182,.18);font-size:13px;}
.quick-actions{display:grid!important;grid-template-columns:1fr 1fr;gap:10px!important;}
.quick-actions .link-btn:last-child{grid-column:1 / -1;}
@media(max-width:1200px){
  .timer-wrap{gap:12px}.timer-box{width:260px}.btn{min-width:105px;padding:12px 16px}
  .stats .card{grid-template-columns:62px 1fr}.stat-icon{width:60px;height:60px}
  .topbar h1{font-size:28px}
}
@media(max-width:980px){
  .dashboard{grid-template-columns:1fr}.sidebar{position:relative;width:auto;height:auto;min-height:auto}
  .main{padding:14px}.stats{grid-template-columns:repeat(2,1fr)}.middle,.bottom{grid-template-columns:1fr}
  .topbar{flex-wrap:wrap}.timer-wrap{width:100%;margin-left:0;justify-content:flex-end}
}
@media(max-width:620px){
  .stats{grid-template-columns:1fr}.timer-wrap{flex-wrap:wrap;justify-content:stretch}.timer-box{width:100%}.btn{flex:1}
  .welcome-wrap{width:100%}.profile-avatar-link{width:68px;height:68px;flex-basis:68px}.topbar h1{font-size:24px}
}


/* ===== FINAL ALIGNMENT FIX — KEEP SIDEBAR WIDTH 220px ===== */
*,*::before,*::after{box-sizing:border-box;}
html,body{margin:0;min-width:0;}
body{overflow-x:hidden;}
.dashboard{grid-template-columns:220px minmax(0,1fr)!important;width:100%;min-height:100vh;}
.sidebar{width:220px!important;min-width:220px;max-width:220px;}
.main{min-width:0;width:100%;padding:14px 20px 22px!important;gap:16px!important;overflow:visible;}
.topbar{width:100%;min-width:0;min-height:156px!important;padding:20px 22px!important;display:grid!important;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:20px!important;}
.welcome-wrap{min-width:0;display:flex;align-items:center;gap:16px!important;}
.welcome-wrap>div:last-child{min-width:0;}
.topbar h1{margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.timer-wrap{min-width:0;margin-left:0!important;display:flex;align-items:center;justify-content:flex-end;gap:14px!important;flex-wrap:nowrap!important;}
.timer-box{width:290px!important;height:118px!important;flex:0 0 290px;}
.stats{width:100%;grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:16px!important;}
.stats>.card{min-width:0;width:100%;height:120px!important;}
.middle{width:100%;grid-template-columns:minmax(0,1fr) minmax(0,1fr)!important;gap:16px!important;align-items:stretch;}
.middle>.card{min-width:0;width:100%;min-height:350px!important;}
#seatPreview{width:100%;min-width:0;}
.calendar-card{width:100%;min-width:0;}
.bottom{width:100%;grid-template-columns:minmax(0,1fr) minmax(0,1fr) minmax(0,1fr)!important;gap:16px!important;align-items:stretch;}
.bottom>.card{min-width:0;width:100%;min-height:245px!important;}
.calendar-weekdays,.calendar{width:100%;}
.day{min-width:0;}

@media(max-width:1200px){
  .timer-box{width:260px!important;flex-basis:260px;}
  .topbar{grid-template-columns:minmax(0,1fr) auto;}
}
@media(max-width:980px){
  .dashboard{grid-template-columns:1fr!important;}
  .sidebar{width:100%!important;min-width:0;max-width:none;}
  .main{padding:14px!important;}
  .topbar{display:flex!important;flex-wrap:wrap!important;}
  .timer-wrap{width:100%;justify-content:flex-end;}
  .stats{grid-template-columns:repeat(2,minmax(0,1fr))!important;}
  .middle,.bottom{grid-template-columns:1fr!important;}
}
@media(max-width:620px){
  .topbar{padding:16px!important;}
  .welcome-wrap{width:100%;}
  .timer-wrap{justify-content:stretch;flex-wrap:wrap!important;}
  .timer-box{width:100%!important;flex-basis:100%;}
  .btn{flex:1;min-width:0;}
  .stats{grid-template-columns:1fr!important;}
}

/* ===== FINAL COMPACT SPACING OVERRIDES ===== */
.topbar{min-height:100px;padding:12px 18px;border-radius:21px;gap:15px}
.profile-avatar-link{width:58px;height:58px;flex-basis:58px}
.welcome-wrap{gap:12px}
.welcome-wrap h1{font-size:clamp(24px,2.6vw,32px)}
.timer-card{height:72px;width:275px;border-radius:17px}
.timer-card::before{border-radius:15px}
.timer-card .timer-inner{border-radius:15px}
.timer{font-size:27px}
.btn{padding:10px 16px}
.stats{gap:14px}
.card{padding:16px}
.stat-card{min-height:94px;gap:12px}
.stat-icon{width:46px;height:46px;flex-basis:46px}
.stat-icon svg{width:23px;height:23px}
.stat-copy span{font-size:12px;margin-bottom:3px}
.stat-copy strong{font-size:25px}
.stat-copy small{font-size:10px}
.middle{gap:14px}
.seat-card,.calendar-card{min-height:300px}
.seat-preview{min-height:210px;padding:20px}
.seat-preview-copy strong{font-size:22px;margin-bottom:5px}
.seat-preview-copy p{font-size:12px;line-height:1.55}
.seat-preview-meta{left:20px;right:20px;bottom:52px}
.seat-legend{left:20px;right:20px;bottom:14px}
.calendar-head{margin-bottom:7px}
.calendar-title strong{font-size:19px}
.calendar-title span{font-size:11px}
.calendar-chip{padding:7px 10px;font-size:10px}
.calendar-weekdays{gap:5px;margin-bottom:5px}
.calendar{gap:5px}
.calendar-empty{min-height:27px}
.day{padding:6px 0;border-radius:9px;font-size:10px}

/* =========================================================
   FINAL THEME + COMPACT LAYOUT FIX
   Dark theme remains unchanged.
   Topbar: 122px
   Timer: 180px wide x 117px high
   Middle cards: compact to reduce scrolling
   ========================================================= */

.topbar{
  height:122px!important;
  min-height:122px!important;
  max-height:122px!important;
  padding:10px 18px!important;
  overflow:visible!important;
}
.timer-box{
  width:180px!important;
  min-width:180px!important;
  max-width:180px!important;
  height:117px!important;
  min-height:117px!important;
  max-height:117px!important;
}
.timer-wrap{gap:10px!important;}
.timer-content{min-width:0!important;}
.timer{font-size:27px!important;}
.timer-sub{margin-top:8px!important;margin-left:38px!important;}

.middle>.card{
  min-height:300px!important;
  height:300px!important;
  padding:18px 20px!important;
}
#seatPreview{
  min-height:222px!important;
  height:222px!important;
  margin-top:8px!important;
  padding:18px 22px!important;
}
.seat-preview-copy strong{font-size:22px!important;}
.seat-preview-copy p{font-size:12px!important;line-height:1.45!important;}
.seat-preview-meta{margin-top:auto!important;}
.calendar-card{padding:17px 20px!important;}
.calendar-title strong{font-size:20px!important;}
.calendar-title span{font-size:11px!important;}
.calendar-weekdays{gap:5px!important;margin-bottom:5px!important;}
#calendar{gap:5px!important;}
.day{min-height:32px!important;padding:6px 0!important;font-size:10px!important;}
.calendar-empty{min-height:27px!important;}

.stats>.card{
  height:120px!important;
}
.bottom>.card{
  min-height:220px!important;
}

/* ---------- LIGHT ---------- */
body.light-mode{
  --bg:#e8eef6;
  --bg-gradient:linear-gradient(135deg,#f8fbff 0%,#dce8f6 100%);
  --text:#10213a;
  --muted:#52657e;
  --panel:rgba(255,255,255,.90);
  --panel-strong:rgba(255,255,255,.98);
  --panel-alt:#eef4fb;
  --border:rgba(41,65,95,.16);
  --link:#10213a;
  --shadow:0 14px 32px rgba(30,55,85,.10);
}
body.light-mode .sidebar{
  background:linear-gradient(180deg,#f7fbff 0%,#e4edf8 100%)!important;
  border-right:1px solid rgba(43,68,99,.16)!important;
  color:#10213a!important;
}
body.light-mode .brand-wrap{
  background:linear-gradient(180deg,rgba(255,255,255,.96),rgba(226,237,249,.92))!important;
  border-color:rgba(43,68,99,.16)!important;
}
body.light-mode .brand,
body.light-mode .sidebar a,
body.light-mode .mode-toggle,
body.light-mode .mode-option,
body.light-mode .theme-title,
body.light-mode .brand-sub{
  color:#10213a!important;
}
body.light-mode .brand-sub,
body.light-mode .theme-title,
body.light-mode .calendar-title span,
body.light-mode .stat-copy span,
body.light-mode .stat-unit{
  color:#52657e!important;
}
body.light-mode .sidebar a:hover{
  background:linear-gradient(90deg,rgba(75,92,255,.14),rgba(59,130,246,.08))!important;
  border-color:rgba(75,92,255,.28)!important;
}
body.light-mode .sidebar a.active{
  background:linear-gradient(90deg,rgba(75,92,255,.20),rgba(59,130,246,.10))!important;
  border-color:rgba(75,92,255,.42)!important;
}
body.light-mode .theme-area{
  border-top-color:rgba(43,68,99,.12)!important;
}
body.light-mode .mode-toggle,
body.light-mode .mode-option{
  background:rgba(255,255,255,.78)!important;
  border-color:rgba(43,68,99,.16)!important;
}
body.light-mode .mode-option.active{
  background:linear-gradient(135deg,rgba(79,70,229,.16),rgba(59,130,246,.12))!important;
  border-color:#635bff!important;
}
body.light-mode .sidebar-footer{
  border-top-color:rgba(43,68,99,.12)!important;
}
body.light-mode .sidebar-footer a{
  color:#b42318!important;
  background:rgba(239,68,68,.07)!important;
  border-color:rgba(220,38,38,.28)!important;
}
body.light-mode .topbar,
body.light-mode .middle>.card,
body.light-mode .bottom>.card{
  background:linear-gradient(135deg,rgba(255,255,255,.96),rgba(239,246,255,.92))!important;
  border-color:rgba(43,68,99,.14)!important;
  box-shadow:0 12px 28px rgba(30,55,85,.08)!important;
}
body.light-mode .timer-box{
  background:#edf5ff!important;
  border-color:rgba(92,78,255,.30)!important;
  box-shadow:inset 0 0 25px rgba(76,99,160,.08)!important;
}
body.light-mode .timer{color:#10213a!important;}
body.light-mode .timer-sub{color:#52657e!important;}
body.light-mode .timer-clock-icon{color:#4d75e8!important;}
body.light-mode .timer-box::before{
  background:conic-gradient(from 0deg,transparent 0 72%,#8b5cf6 77%,#6366f1 84%,#3b82f6 90%,transparent 96%)!important;
}
body.light-mode #seatPreview{
  background:linear-gradient(135deg,#eef6ff,#e1edf9)!important;
  border-color:rgba(56,92,132,.18)!important;
}
body.light-mode .seat-preview-copy p,
body.light-mode .status-text{
  color:#52657e!important;
}
body.light-mode .seat-preview-badge{
  color:#243b67!important;
  background:rgba(99,102,241,.12)!important;
  border-color:rgba(79,70,229,.28)!important;
}
body.light-mode .miniSeats,
body.light-mode .seat-legend span,
body.light-mode .calendar-chip,
body.light-mode .diary-date,
body.light-mode .link-btn{
  background:rgba(255,255,255,.72)!important;
  border-color:rgba(43,68,99,.14)!important;
  color:#10213a!important;
}
body.light-mode .calendar-head{background:transparent!important;}
body.light-mode .day{
  background:rgba(255,255,255,.72)!important;
  border-color:rgba(43,68,99,.14)!important;
  color:#10213a!important;
}
body.light-mode .day.today{
  background:rgba(59,130,246,.16)!important;
  border-color:#4d75e8!important;
}
body.light-mode .diary-box{
  background:#f7fbff!important;
  color:#10213a!important;
  border-color:#7656d8!important;
}
body.light-mode .diary-box::placeholder{color:#687a91!important;}
body.light-mode .btn-green{
  color:#166534!important;
  background:rgba(34,197,94,.08)!important;
}
body.light-mode .btn-red{
  color:#991b1b!important;
  background:rgba(239,68,68,.07)!important;
}

/* ---------- GLASS ---------- */
body.glass-mode{
  --bg:#07111d;
  --bg-gradient:
    radial-gradient(circle at top left,rgba(56,189,248,.18),transparent 28%),
    radial-gradient(circle at top right,rgba(236,72,153,.14),transparent 24%),
    linear-gradient(135deg,#07111d,#14263d);
  --text:#f8fafc;
  --muted:#cbd5e1;
  --panel:rgba(255,255,255,.08);
  --panel-strong:rgba(255,255,255,.14);
  --panel-alt:rgba(255,255,255,.09);
  --border:rgba(255,255,255,.20);
  --link:#f8fafc;
}
body.glass-mode .sidebar{
  background:linear-gradient(180deg,rgba(10,31,50,.82),rgba(4,18,31,.72))!important;
  border-right-color:rgba(148,210,255,.20)!important;
  backdrop-filter:blur(16px)!important;
}
body.glass-mode .brand-wrap,
body.glass-mode .mode-toggle,
body.glass-mode .mode-option{
  background:rgba(255,255,255,.08)!important;
  border-color:rgba(255,255,255,.18)!important;
}
body.glass-mode .sidebar a{color:#f8fafc!important;}
body.glass-mode .sidebar a.active{
  background:linear-gradient(90deg,rgba(88,70,255,.36),rgba(38,166,255,.18))!important;
  border-color:rgba(126,109,255,.58)!important;
}
body.glass-mode .mode-option.active{
  background:linear-gradient(135deg,rgba(139,92,246,.34),rgba(59,130,246,.20))!important;
  border-color:#8b7cff!important;
}
body.glass-mode .theme-area,
body.glass-mode .sidebar-footer{border-top-color:rgba(255,255,255,.12)!important;}
body.glass-mode .topbar,
body.glass-mode .middle>.card,
body.glass-mode .bottom>.card{
  background:linear-gradient(135deg,rgba(10,30,50,.72),rgba(10,23,42,.58))!important;
  border-color:rgba(160,205,245,.20)!important;
  backdrop-filter:blur(14px)!important;
}
body.glass-mode .timer-box{
  background:rgba(5,18,35,.66)!important;
  border-color:rgba(150,180,230,.28)!important;
  backdrop-filter:blur(12px)!important;
}
body.glass-mode #seatPreview{
  background:linear-gradient(135deg,rgba(36,94,145,.25),rgba(255,255,255,.06))!important;
  border-color:rgba(145,200,245,.25)!important;
}
body.glass-mode .calendar-chip,
body.glass-mode .miniSeats,
body.glass-mode .seat-legend span,
body.glass-mode .diary-date,
body.glass-mode .link-btn{
  background:rgba(255,255,255,.08)!important;
  border-color:rgba(255,255,255,.16)!important;
}
body.glass-mode .day{
  background:rgba(255,255,255,.055)!important;
  border-color:rgba(170,210,245,.15)!important;
}
body.glass-mode .day.today{
  background:rgba(59,130,246,.28)!important;
  border-color:rgba(96,165,250,.55)!important;
}
body.glass-mode .diary-box{
  background:rgba(4,18,34,.70)!important;
  border-color:#8b5cf6!important;
}

/* ---------- SUNSET ---------- */
body.sunset-mode{
  --bg:#24121b;
  --bg-gradient:linear-gradient(135deg,#24121b,#4a1935,#6b2a13);
  --text:#fff7ed;
  --muted:#f2c9a5;
  --panel:rgba(58,24,38,.72);
  --panel-strong:rgba(90,35,48,.82);
  --panel-alt:rgba(73,30,43,.76);
  --border:rgba(255,210,170,.20);
  --link:#fff7ed;
}
body.sunset-mode .sidebar{
  background:linear-gradient(180deg,#321827 0%,#21111c 100%)!important;
  border-right-color:rgba(255,190,150,.18)!important;
}
body.sunset-mode .brand-wrap{
  background:linear-gradient(180deg,rgba(119,52,42,.38),rgba(47,21,32,.48))!important;
  border-color:rgba(255,205,160,.18)!important;
}
body.sunset-mode .sidebar a,
body.sunset-mode .mode-toggle,
body.sunset-mode .mode-option,
body.sunset-mode .brand,
body.sunset-mode .brand-sub,
body.sunset-mode .theme-title{
  color:#fff7ed!important;
}
body.sunset-mode .brand-sub,
body.sunset-mode .theme-title,
body.sunset-mode .stat-copy span,
body.sunset-mode .stat-unit,
body.sunset-mode .calendar-title span{
  color:#f2c9a5!important;
}
body.sunset-mode .sidebar a.active{
  background:linear-gradient(90deg,rgba(124,58,237,.34),rgba(234,88,12,.20))!important;
  border-color:rgba(168,85,247,.52)!important;
}
body.sunset-mode .sidebar a:hover{
  background:rgba(255,255,255,.07)!important;
  border-color:rgba(255,190,150,.18)!important;
}
body.sunset-mode .theme-area,
body.sunset-mode .sidebar-footer{border-top-color:rgba(255,210,170,.12)!important;}
body.sunset-mode .mode-toggle,
body.sunset-mode .mode-option{
  background:rgba(255,247,237,.07)!important;
  border-color:rgba(255,210,170,.18)!important;
}
body.sunset-mode .mode-option.active{
  background:linear-gradient(135deg,rgba(124,58,237,.32),rgba(234,88,12,.20))!important;
  border-color:#a855f7!important;
}
body.sunset-mode .topbar,
body.sunset-mode .middle>.card,
body.sunset-mode .bottom>.card{
  background:linear-gradient(135deg,rgba(61,25,42,.84),rgba(42,18,30,.78))!important;
  border-color:rgba(255,210,170,.18)!important;
  box-shadow:0 14px 34px rgba(36,10,18,.30)!important;
}
body.sunset-mode .timer-box{
  background:#2a1725!important;
  border-color:rgba(192,132,252,.30)!important;
}
body.sunset-mode .timer{color:#fff7ed!important;}
body.sunset-mode .timer-sub{color:#f2c9a5!important;}
body.sunset-mode .timer-box::before{
  background:conic-gradient(from 0deg,transparent 0 72%,#c084fc 77%,#a855f7 84%,#fb923c 90%,transparent 96%)!important;
}
body.sunset-mode #seatPreview{
  background:linear-gradient(135deg,rgba(104,48,59,.76),rgba(54,24,38,.78))!important;
  border-color:rgba(255,195,150,.20)!important;
}
body.sunset-mode .seat-preview-badge{
  background:rgba(168,85,247,.20)!important;
  border-color:rgba(192,132,252,.42)!important;
}
body.sunset-mode .miniSeats,
body.sunset-mode .seat-legend span,
body.sunset-mode .calendar-chip,
body.sunset-mode .diary-date,
body.sunset-mode .link-btn{
  background:rgba(255,247,237,.07)!important;
  border-color:rgba(255,210,170,.16)!important;
  color:#fff7ed!important;
}
body.sunset-mode .day{
  background:rgba(255,247,237,.055)!important;
  border-color:rgba(255,210,170,.16)!important;
  color:#fff7ed!important;
}
body.sunset-mode .day.today{
  background:rgba(124,58,237,.30)!important;
  border-color:rgba(168,85,247,.55)!important;
}
body.sunset-mode .diary-box{
  background:#2a1725!important;
  border-color:#a855f7!important;
  color:#fff7ed!important;
}



/* ===== FINAL USER REQUEST: ONLY CARD THEME + TIMER ALIGNMENT + SEAT OVERLAP ===== */

/* Keep the 122px topbar and 117px timer, but center the timer cleanly inside it. */
.topbar{
  height:122px!important;
  min-height:122px!important;
  max-height:122px!important;
  box-sizing:border-box!important;
  padding:2px 18px!important;
  align-items:center!important;
  overflow:visible!important;
}
.timer-wrap{
  height:117px!important;
  align-items:center!important;
  justify-content:flex-end!important;
  flex-wrap:nowrap!important;
  margin-left:auto!important;
}
.timer-box{
  width:180px!important;
  min-width:180px!important;
  max-width:180px!important;
  height:117px!important;
  min-height:117px!important;
  max-height:117px!important;
  flex:0 0 180px!important;
  box-sizing:border-box!important;
  margin:0!important;
}

/* Fix the Seat Booking content overlap without changing its overall design. */
#seatPreview{
  display:grid!important;
  grid-template-rows:auto 1fr auto!important;
  align-items:stretch!important;
  gap:8px!important;
  overflow:hidden!important;
}
.seat-preview-copy{
  min-width:0!important;
}
.seat-preview-copy p{
  margin:0!important;
  max-width:none!important;
}
.seat-preview-meta{
  position:relative!important;
  left:auto!important;
  right:auto!important;
  bottom:auto!important;
  top:auto!important;
  width:100%!important;
  margin:0!important;
  align-self:end!important;
  display:flex!important;
  align-items:center!important;
  justify-content:space-between!important;
  gap:12px!important;
  min-width:0!important;
}
.miniSeats{
  flex:0 0 auto!important;
}
.seat-preview-badge{
  flex:0 0 auto!important;
  white-space:nowrap!important;
}
.seat-legend{
  position:relative!important;
  left:auto!important;
  right:auto!important;
  bottom:auto!important;
  width:100%!important;
  margin:0!important;
  align-self:end!important;
}

/* The four stat cards had hard-coded dark backgrounds. Give them proper
   theme-aware surfaces while preserving the existing icons/colors/layout. */
body.light-mode .stats>.card{
  background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(239,246,255,.94))!important;
  border-color:rgba(43,68,99,.14)!important;
  box-shadow:0 12px 28px rgba(30,55,85,.08)!important;
  color:#10213a!important;
}
body.light-mode .stats>.card .stat-copy span,
body.light-mode .stats>.card .stat-unit{
  color:#52657e!important;
}
body.light-mode .stats>.card .stat-copy strong{color:#10213a!important;}
body.light-mode .stats>.card .stat-icon{background:rgba(255,255,255,.68)!important;}

body.glass-mode .stats>.card{
  background:linear-gradient(135deg,rgba(10,30,50,.72),rgba(10,23,42,.58))!important;
  border-color:rgba(160,205,245,.20)!important;
  box-shadow:0 12px 30px rgba(0,0,0,.18)!important;
  backdrop-filter:blur(14px)!important;
  color:#f8fafc!important;
}
body.glass-mode .stats>.card .stat-copy span,
body.glass-mode .stats>.card .stat-unit{color:#cbd5e1!important;}
body.glass-mode .stats>.card .stat-copy strong{color:#f8fafc!important;}
body.glass-mode .stats>.card .stat-icon{background:rgba(255,255,255,.06)!important;}

body.sunset-mode .stats>.card{
  background:linear-gradient(135deg,rgba(61,25,42,.84),rgba(42,18,30,.78))!important;
  border-color:rgba(255,210,170,.18)!important;
  box-shadow:0 12px 30px rgba(36,10,18,.28)!important;
  color:#fff7ed!important;
}
body.sunset-mode .stats>.card .stat-copy span,
body.sunset-mode .stats>.card .stat-unit{color:#f2c9a5!important;}
body.sunset-mode .stats>.card .stat-copy strong{color:#fff7ed!important;}
body.sunset-mode .stats>.card .stat-icon{background:rgba(255,247,237,.06)!important;}


/* =========================================================
   100% BROWSER ZOOM COMPACT DESKTOP LAYOUT
   Preserves the existing dashboard design and sidebar width.
   ========================================================= */
@media (min-width: 1201px){
  .main{
    padding:12px 18px 16px !important;
    gap:12px !important;
  }

  /* Top bar */
  .topbar{
    height:122px !important;
    min-height:122px !important;
    max-height:122px !important;
    padding:12px 18px !important;
    gap:14px !important;
  }
  .profile-avatar-link{
    width:62px !important;
    height:62px !important;
    flex-basis:62px !important;
  }
  .topbar h1{font-size:29px !important;}
  .profile-mini-link{
    font-size:12px !important;
    margin-top:5px !important;
  }

  /* Live timer */
  .timer-box{
    width:180px !important;
    min-width:180px !important;
    max-width:180px !important;
    height:117px !important;
    min-height:117px !important;
    max-height:117px !important;
    flex:0 0 180px !important;
  }
  .timer{
    font-size:27px !important;
  }
  .timer-clock-icon{
    width:25px !important;
    height:25px !important;
  }
  .timer-sub{
    font-size:11px !important;
    margin-top:7px !important;
    margin-left:36px !important;
  }
  .timer-wrap{gap:10px !important;}
  .btn{
    min-width:116px !important;
    padding:11px 17px !important;
    font-size:14px !important;
  }

  /* Four stats */
  .stats{
    gap:12px !important;
  }
  .stats>.card{
    height:108px !important;
    min-height:108px !important;
    padding:14px 16px !important;
    border-radius:18px !important;
    grid-template-columns:60px 1fr !important;
    column-gap:11px !important;
  }
  .stat-icon{
    width:52px !important;
    height:52px !important;
    flex-basis:52px !important;
  }
  .stat-icon svg{
    width:24px !important;
    height:24px !important;
  }
  .stat-copy span{font-size:12px !important;}
  .stat-copy strong{
    font-size:25px !important;
    line-height:1 !important;
  }
  .stat-unit{font-size:10px !important;}

  /* Middle section */
  .middle{
    gap:12px !important;
  }
  .middle>.card{
    height:298px !important;
    min-height:298px !important;
    padding:16px 18px !important;
    border-radius:19px !important;
  }
  .middle h3{
    font-size:21px !important;
    margin-bottom:8px !important;
  }

  /* Seat booking preview */
  #seatPreview{
    height:212px !important;
    min-height:212px !important;
    margin-top:6px !important;
    padding:18px 20px !important;
    border-radius:17px !important;
  }
  .seat-preview-copy strong{font-size:22px !important;}
  .seat-preview-copy p{
    font-size:12px !important;
    line-height:1.45 !important;
    max-width:610px !important;
  }
  .seat-preview-meta{
    left:20px !important;
    right:20px !important;
    bottom:55px !important;
  }
  .miniSeats{
    padding:5px 8px !important;
    gap:4px !important;
  }
  .miniSeats img{
    width:26px !important;
    height:26px !important;
  }
  .seat-preview-badge{
    padding:9px 14px !important;
    font-size:12px !important;
  }
  .seat-legend{
    left:20px !important;
    right:20px !important;
    bottom:16px !important;
    gap:12px !important;
  }
  .seat-legend span{
    font-size:12px !important;
  }

  /* Calendar */
  .calendar-card{
    padding:16px 18px !important;
  }
  .calendar-head{
    padding-bottom:7px !important;
  }
  .calendar-title strong{font-size:21px !important;}
  .calendar-title span{
    font-size:11px !important;
    margin-top:2px !important;
  }
  .calendar-chip{
    padding:7px 11px !important;
    font-size:10px !important;
  }
  .calendar-weekdays{
    gap:5px !important;
    margin-bottom:4px !important;
  }
  .calendar-weekdays span{font-size:9px !important;}
  #calendar{
    gap:5px !important;
  }
  .calendar-empty{
    min-height:27px !important;
  }
  .day{
    min-height:31px !important;
    padding:6px 0 !important;
    border-radius:9px !important;
    font-size:10px !important;
  }

  /* Bottom section */
  .bottom{
    gap:12px !important;
  }
  .bottom>.card{
    min-height:220px !important;
    height:220px !important;
    padding:16px 18px !important;
    border-radius:19px !important;
  }
  .bottom h3{
    font-size:21px !important;
  }
  #weeklyChart{
    height:145px !important;
  }
  .diary-box{
    height:122px !important;
  }
  .quick-actions{
    gap:8px !important;
  }
  .link-btn{
    padding:10px 12px !important;
    font-size:12px !important;
  }

  /* Reduce unnecessary whitespace in the main content */
  .section-title{margin-bottom:8px !important;}
  .card{box-shadow:0 14px 35px rgba(0,0,0,.24) !important;}
}

</style>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="brand-wrap">
                <img class="brand-mark" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHsAAABcCAYAAABObsrxAABJn0lEQVR4nH29ebRlR3nY+6uqvfeZ7ty3Z/UgdWueJSRAEqMEiMnGxAPGgG1skxivhMRxsjL4vdhOspadPMd5cfzs5OEXYGXh2MYD2BCDAQEGjEY0tlpq9Tz3nc+4p6p6f9Qe6px75dPr9D3n7Np7V33z99X3fVu0Fw9Zipe11cex16v8jBDFcSwgvIEWi0BsOt+NdMPdeCEE1uLGFieIeuSm84vT6vOELY6L6pg3w2KetrpOOef6ipNrtd6Z7trVb9XJYvxM66/XYgXe6us1iBpg7o8AUXyxNbTG5r7pBCaARb02Js50MLLV38D/Uk7Z2uLv2HwLoArGkCiEoMYgFeCLMzbNuQKCdeisCKyCnxhDNKJAUHnMQ5goFu0jUIwBwl++HUd08aVaiymRXMy9XCzWQ7K3kPKvLRFVzrqAxybC8wjYoxWPpIv7lPcvjwpKkhDVhT2GEKLA2+ZXCdvyHrL+sb6tQIDwL1BPsqYa90ONXDGxuPHriQpr1Y+TU3PrEOW9ivElxZXI8QAihKi+CyGrebjfN49FiPoc4c4RQrpbV9dii2uPHyvXaW0tpPz1bKKNgoTEGFisYzAPaLa4YI1oijHFzfwLCGrpWF69WnN93HoTDhxlVqRSwmvshtWUa2KvrlfRuC/Cfe6z9ahxwSOceN1E4aV48xc4PqDkOp/wbPHBQ1f9v8/FpTTwb1yKjkLyuCkbvFPHkOJgXMxBUGN/QrqXE3NjxJikG1tBCVfG1ZcnZ+oBwlKqyRoeHu+P4Wics4MamDUAxnW3HeMu/+bl9UpxsuWiRQ1UB1dbEcDkbWrAFueNAdoTnQWhjOtGT9yW6qJkJ49gLBYhpftqTMEQEmuLz9JNYFIEl6qunoN33WoNE5r6VSRddbIo51qoghKg5e+iHuq4vuBeX9UUsLGeDVRDCYTHeUEpwnwWE57etBM6sZq6h9iaQT355pGFA5a35E0saT3d713CB03Flhakz+5i0/FxRAtkMdJiCaSqry0luTaeyKcQiwXwMTW0fe3qC5wSftZ6UPMQN7kW6xBTSeRa3BS3GaNMTxK532xJsB4EKjj6zOJDqDgmLcWa7OSkah3iI8FY60DgcYxbl6nGOJ1YffM0lqAwE4pJeaLRu6YoqVuUulN650nH9AL3ezFOClmrDinq4UIgpCPYQAUIBPFoRDIaIoQgCAJKYhNMAGBMLPgU79nQYgJntf7CVm/vmiWifZwWHFoitBSGCDvGVP7LeNKuvK2YgGlFCBXkitGexq3+lcuyY8ZEfROLTyBbsL+ASRbdbDkXPDeh62owT+iw8kBxc0HpQbjzpJQIIZBCIaVCFlIgDEKEgHQ04sMf+Rg/9oGfJO72kMJWCB+zaX08TwDRk1flwQKhPiB9lbLFa1KqF9ehmIkPkBL2m0/xDUex1bTHphkI/4dakk+MLEXKVnJ2KySLceosab9Ari8Cy/FbvcYsfjF5p0n1YgHpuNpaZ2kX3K2kIIoili5e4L773s3PfOyfMOoPefHZ4zz17DeZXdyGMfm4cTNhfvg6e7MrBsJKD9Ilo/iizxsD+KizBXxtadAVR62ZcPs8u2irObiLldTlJKr15iM3D3b/jZsamyD96q+Cm0sU+0sSY9/K6776hUvJMaYVKojW0qbUuYUfiZQSJQVKSqQURK02a2tLXHv4Hn7q7/8LEt0mbC7yoY/+U3Zuv4HBxhph1HRGWiXBtrLsJ+cqxubjjxyT/oXrNi4h8T77GriWMONBIo/pPAlSyVprPHjXOt6fsSwvPw7iyW92fO6bgFDq0PKzqEV0sVApqZAiZEGZEg8YbH5ZEGJ8PuV9xoaVCJcWKQRKOCQLoNnsMBxsMD21i4/87D/m+puu58rlPkvLI2678x5++Ed+FskUSTwkCJsY62wBKUuQCaTnu251/wrovvQd13ib4Tphr1TvMenniVusM4sEhc3iSY1xTTc2p8qzsCDHQ5yFkWWKo9aCNZtMFM/i32LVJTk6TqvspMpQK44J6ndx7cqA8Cbu38d6i5NSVu8xl6ugOGs1QRSS5zHpSPPDP/ozvOEtb2Z5aUAgJHlsWVtOePChH+TBt/wY8cYIYTVBGBZQlwipkEI661mU85feewLrwptzxaU49+fVbBostdVfQKp0n/zoZiHhaiCUUC0/yuK7HMO/j93NYty/baVmxyM99ctW6PNVeKEtHAdjkUU0TvjIFY5jZBXtAiRISUUFJWf54smWdy0WXyLczVe66wEqCFEyZGN1gze9+e/x7vd9kI0eDPsGYSQQsLGhyfUU73//x7nj9nfQX1lDqRAhQyAAqUAqhFAgFCAdCVvfgKSQquPK3lDzzBjUffh6nyrJ4A8fEyGeESEmDca/61VPQlb+rXUujoU66GAdFkoNVhsoxVtSGQD+vKwneh1C6pUJCjEuPTTWkU6EcMRRSQRZLlpUKqI2cuoQrZQSWYhvqQRR1GR9ZZUbbriPD3z4FxBqjsuXM6BBnEoyLVAqYHVlyOzcLn78A7/I/v1301/vosIWSIVFAQpRIFlIgS3nU7CZo1EXcjXF20nIWpcbCwYzhs2KS4WsbCoBYGrGEpVNUnO6xTq3q4B7Hf00YI2zOzbrEKh43qOBTXSxhc6pbAR/4sUFy++lb4t0HKekcHq0RHQxRsqSu2vdKLzr1Ej2CEYIrKxFVulySeWs3ajZZNjvsW3HVfzIRz7O4u6DXLqUIExEkkrSVJGnAp1ZMIorl4Yc3H8bP/S+f8j01A6SUQ8RhA6VUhZSu5g4JaGW6xUIY4uZjOvYTbbFJGw9RGz+NLHoYt0V8L3ftjJ3Kjx415B1pKwiSxdGrIBdHt96omJikVJ6E6j0KhXHyUAQKklQWMpSChR1UEz4ANvSLvAsUl+MC2e5BGFInqcgFO/+wY9wyx33c+lKhtYB1iiyVJJl7m+aCnQuyFPJykrGXXc8yJvf+ONkoxyhc6RSBZfJCrnSs0SslGhtybO01qUTyKg/i2rOUJk1YCekor/S8thEXF5MivEJN1iM3bOkGVuuwnqHJq1f4VGxdwnfIixetcVdc7bjXMcNUggCFWCFrLhaCOuIodTlvhWxhYpwc7JMwIAyuCKDgF6vx+13v4v73/A+4r4hH2lCCVpbjBboTJBlgix1b4RiMMjI04AH7vsg11/7FkbdDZRSHgZK6DikyaCBTRMWF/Zwx21vZiqMwFhkEBTniL+DUJlAR/mpVFc+R9cn+LH56qgfbKlEYAERW8OoELKevhhj+9qarC9QY0IKb2oFcmtkCKS0qEIHC+UQrpQikJZIjoiCjECVLpkt3vX55TQcvg3jFqunk0rfGFBBwGjQZXbuEA+8+UdpdLaxsZbQDAQYjbUaYwxaW3SO42oNeW5RUrK6PGR+ejf33/dBmtEuslEPGSgMBlOEgw0GIyy5SckHq9x56/387I98nFnVxsR9pFAe7Gzlu1NIyzo2OSmyi/VW9o0XUKkEgh1DuC3j3N4vbo6emVeCyJRKbwvH0aeKanIlRZQz8G4sx0YWIySFroYgdDq11cp5/8PbufFwE2tywkCiVDmu1H1esF74UQCHdFsh31SLLpXNcDji1tveytWHbqG7kYEOsAaMtuBsGIw2WGMx2qIzg8ksRhu0FvTXMw7vv5cbrn8LyWCAwIDVWJNjTYbCYI0m2+izZ/Zm3ve6d/PGm2/jtv33QK7IY420IDAIdAETMwEZPxhdws0jkPJXa2s8WLYW9xNCoN4TL0PQ9Z0lgsq6m0CVNxnGyHDS1isFfek1ORer8KVlGa5UpMawYyHi5z50HXfeOI3OLIFSKFVGvKDaKKBGqMDjjoJDsAaLxhZcJ4Ugjoe0p3Zx4y130Wi0iHsaYSV5JtG5RGuJ0cWltEFnBnLQuSVNwFpJbyOjKWa58fAbaUQ7SIcbSCzCZAibY+IRUS552/0/yn/9P3+P97zmdczKBp/42V/i5z/wr7jt0PWo3CKMRmKRotxGnVCDwvvsAVV4gN1sMDvguo2oWuxvxnt9ESucd+T87Fe9cP3rJN1VF5di/MzCkS79XyEFMpBErYhWu0GaWa4/OM2hwwvcc8sC81MKIwLarYggcJwvvX3s8uaOQj0xbq1LI6p9HaSQJPGIbduvZnHXAZLEkiWSPHfIzguRrbUlTw1pYskzyDNIU0sWQxxb4sTQ38hZmL6OHQuHyUZ9Jw50DkZjbIbVKVJaLqxscPbcClkqWe2naHIQhtxoTBmbMKY23koxa2vJ6dxHiRW+wTMuaSv4V5zrhaI3GbFbpygBBA5HZd7ZZkQ7Q6gwHbe6iiiFUr1HW24xyiAg14bB2pCeVMyFggffuJfpxWne9o6DfP27q/zpI110rlBIBKZww+rEwvFXbfkLBFgX6JfFOKNzOtMLBNE8ydC5Vnl5CqUIdzM12pKOLLYgAJEbtLFkmcXYDGGnaLe2O9FfShBtkYEizQd8+Ruf5Mvf+Bwfe/cv8663vp1/9Z9+jWOXvgDBLIQKgVMNIBHCVIkWFoGQEikVWDCVpGI856/YTNpqr8tDD26En8KB56JVKAIEgS/WDR4+PaYtzf9qo768iZ0kQsdlzgMGYTPmZxRX72yxd2ebu27dwUMP7cekKdt2N/nohw+w1jvGEy9kpHmxTy0lstDVSMcYVVJA5TkUprsFUTjuFgnWEgYB2IA8NujMIk2p9wU2d1yVjPokA4PNO5gMJyVyF/zIUqdhjQ6QogVWVpsMAFobUJLG7BzpcJ2vPvtnnF47wYm1Jwja89ggwGR5rWgrQ6pWiSY36DQGFRA0mlibszn8WrOs/6ssCKYAdMXem9RthSZRpXkFjOnrzYqk5COErSht3BUrnRHvVGORQqHTAYf3LvCr//hO7r1/F0y1II5J+jFprHnNa3fym1dNc+RYyt88foW//NJxzlzUCOU8QmHLewoExokxUbsmolAVLsqhQEiEteSZJcWQJwbnEoA2EqEVcT7g2Sf+ikDOc+jw20lGQ7AajMBqS54ZjJAYK1yYlMKatWUGLqAtGRoRznD6ykucvHgMghQrQWYpY35uJZULEW4sATA3E5Jp6KU5hMVNKhFUy7BNwlSU0rb+QZToLk8vmNCXCZZCjJcb/9UdyhMqwqkvJAuAj5GSFx4t6VFbjRABl5YSvva9y1xYGnHomlluuGmRIFQYC6NezupA0pkOmZ+xBEEBHCnAStDOB3f6T1auobDFxoQsskOpI3NZEhMPMmQISZKjlZtvHltUEDJIBrx45MtMd67jwP6HSYY51uZOFpmC60SAxhQiVoAXSHFAoBQ5IDNn5QpRiO3ShxKFADJIYVw8IWyQ9jO2z0v+0Q+/jqNn1vnUXzxOq72TLEuxnl1S+9rj8rgmAj8nrhhfW8zVHytq/RCUA8eooOR2O2lDWs9aLn1hT4QUet8K0LkhDENOX4759d99nk6oeO1tC/zbf30Xt9w1j9IZn/vicf7zpy5w5kJKnqdIpQhDhdYGKVyY0pqCDkvY4iFdiArZCIFUkuFglX63SzC9izg2BEpjrCQfWWQD0ixF6w2S0TpxX5PFFq21Q4Z1LpqShsQmZNmouKkCaV3yAcat2Sq33sLlMKZMZLSOGkSRRSNw27TGEg8SbNpje3snH33zAR55JuQzf6EZrfagGaJCZ6j4vDQeOCoZyuPKctCYPp0gEkoxXsWjx8+vkghLZTlhtQsvP6oK35WUV/h6WmsaUUTUkTTDiEee6fGZzz7Lr157DyePX+G/fvYVjp6TLHYirIY0z8l0sblQ6GxtASuRsr6fLJFchEixjqtk0GB99QKryxfoNG4kGRpyJcitQQ8MgYXEjEjSNUy2zKDfx8SWLM8Qwm1nkmsaYUQvuUR3cB4CVYT3XLBEIikzSypkWOG0SOU3yWqDyAqLsQIpNDtnMw4sbuNH7zvEXDvm5j2CT7znJr7+0ionlzL6mUWqwkLZlIY0zrab0TkxtqKH2rAOvCyWMSqoTrOMiWkxKStwYrbcxLAT4/JcY6wGK2hEAZ//xhIfeP9lvvzN8xw9HjM71cGSkmqN9ux6pyq87U/rE2WxgKqgwGJMRhi26HYvcu7U8yzO3keaGKzWGCHQI02roYmTAVnaw4grjIZXkPk0STICGSCtQmgQIuPiyhHWesdRUcNFzwSVqhO+SK83mcfsxwIySKXQiabTlPzIw7fyL99/Lbu3KXrdEdcdmuE//dPX890nl/jEZ57liReuEMxMo3W2CZHjdtIEosVEFU0hdW0xUpRi3HFoeSm7+UKvQj9liRDeOaWWcSk4hey1zt1J4hxjBWs9w7cfXebIsQHWKvLMYqyLaEkhXHjSWrQFaUBbgbQGrQFjx+dogMIKhxyhAgQpx174Cru2vYmZqWvpd1dRQYhOclRTMxr2SZINQhmRxiuEhGTJCGSDPLW0wmlW++c4eeGr5GaJoNnE2Jwy+cIWGTCi4GgHOFkzTGVAWve7ENgArFCcvmT44pPLvOH6aQ7vnaa3kfPcUxf44mPn6W7EiNBF+7CSyQ2mcRac4GufY8VmhiwJIZg8fZw/J+7h47/UEYU75lugtTUonB0jQAvn2kip+OI3L7LWdam9VpsiE0QSxwlJJhAyHIuPIxRKCGygnRukDcZYMAIXYXP3NlbTaHVYXX6cF5/9A26/6xexqslwGCO1C6ykoyG57iNthzQeIVSOsTk6swgirBxx+vxfsrT8LWQDjM3AFJZ5sQPmcukFgVL1XoK1WGNcqrWxGKPRxqCUpNFsMcoNX3jkGF/42lE+9gM38F8+8QAvnL3AP/it73HkSh8aLVSzgbH5FgAv8bM1Z4/nONSInrTIg0nze5KefLPA/6m6finGisCL8yqF09vG4UPifFmLJTeCx5+NUSpASul+VwJtBNt3NGlNW9IkIBlJ4pEmTixprElTQ24NMoAwEEhhMcJgtZNMZVWnCSRBQ3HslT+iPbWXw9f/BPEwIE9STAb5MIUccpmTpgPCVk6uY6Ro0WoFXFj9KifO/QGIPkI0sJkuUpOclyCQKCkRVmAzQ5JZTF7AL7Co0BJFlmZD02hKdB7R6zsYTM3PMOrHPPL8Kt95coPHjixx5Eqf6e3bidMYY2pE+4j1GXEML1W1TUkKZWRtvAihPC+o91NrS9CJogmLb8LUrwu/PKIqxsmCq8vcKz/bwn0P0aawoIVFSsVqN+Ynf+pGfvzHt3N5pc+li4aV5ZSlKzGXLg85d37A2dMjLp7L6fYMAkujIQhCZ7Ib7YhVZxoZNDHZiOee/X3CYIr9B9/LwGgMGmMTrHFBklynzoo2AVGnyaX1b3Dk+CeJzWVks4XVebG7p1HScbfRhmSQYrWEBsxvC1jY2WJ+e8Tc9oBt20J2LIbs3xWyuH2WL/3FEp/79AlaMx3yPMOQsd7X/P63znDx/EVUEJFkOUZDUXtU4aJEZqUwJ5IUJyOMtUFNXS/n0UVQjSopoLKw/25T3lovg9SdUB0z1gk7/zQ/6iOK/VFjS3MMUg1Rx7BzV8DMYpsD1yukUs4iN5rBMObMiSGPf7fPd761wrGjXZYv5sRDQ7MVEoYCa3K3bZJpokaHbHSJZ575L4jAsG/fO1HTDbIrmTOwrEXbHNFoI+yIc8tf5aXjn2SYnSNsNZ07hnI+slKYTJDFGQQwOy/YfWiGW1+zk9c9sI0bb2+yuGjodCztQBFY2BY0WBko/veXT2LTDKkMOtOoKGC1P+CPvvI4QlhMc8pF8MqNfA9LwttVLBHka+MxVrOFYYvbLi79An9U4Jvim0yxinm3IgCfEDafWFGZKM7z3YBCelhAGzdWCrh4KeHUlYQ87zEaWKwUmGJjJIwU2/c2+OGfbvHOH53jxWdHfPPLqzz6jSVOnciI04h2q4GUTqfr3BC2Z0mTFb7/6G8x7K9x030/C0GC1SCURod9ksYSr5z8HGdO/zk22CCansXkCUqoInnOkAwMKMO+awNuvW+et75tkXvvn+WqXSEmS+gPVxl2NSsrlitakGWa+YWA0ycNzz2xCkFQqLGC/pUAEXrWxiQ4S0apGWRTjNxOIH4ypctDYTkuqPFpq8F+IruwYpyMKsOs9OVKnV1T5Lh4sW5Hh9qYsiUVFqfmGnRmWe/GaGsIIolIbbFz5qyAzOSMNmKWVwxShlx3XZvX3jPLuZ/cxx985ixf+fwFVpYiGs0moaoNpKg1jU6GPP/0f2VorxBGAhtFoOBS9zscu/glls8/TtRSyHAaY1KkChCBJEstOjUs7oMHf3AvH/rxbdx0Q06uBVfWV3nhJYPREATSGWtSIaREK4ttRAxyTbfvoGqsKSRZ8ZIFm44l8tkKi5Vm9RBa437c1dqkUIWP6hr1QYlcz12krF4oUMVkUputqM0z832RXq5BuWBHlsSEUYQKZJV7ZaypxJTRTqwuXRnR66fMzuWkOnc6MrdYWWSySOkibLlltdtnZVUzNzXFP/s3V/PAw3P8v791nGce7aPNLI2ogcWQa03QmEIozctHv4CImoSdGXIMZ859E6ymOdfBYNE6Q0gXZ0rTHOSQOx+c4ed/6QAPva7JyqUhR44PyXF2hlQKGUqMkKQWrNZYrclTTTONWF5NWV9NHDx1XiRdOGC5ULjnr1vLpLfsfhfjbFoh16sZ9SXuBB58zAUTKt/zqkqDTVSMWylhC7aos64L5z0kF+tIk5ROq83i4jxLK2sMhzGNZlRcxrlcDtkaJeDihSHr6ykz84ZEZygp0KUPrgqj0Tj3WoYBKlT0RyNWj/W5+dYWv/LbN/LJ37nEX//ROkk6RaPZAJ1iMosVIVGzgUaji/UGjZYDmnFbmFZGznBLEhrTMe/88C4+/vN7mAoSHn9uHSUFQdhw4WBjyKzFSJfu5PSuI1ydCYKB5eKlhMFaSiAjjC6SEq2LszuElQUYNezHksZsDe8ScVZ4JcmlEWxt5fKXrnD9ub6kyxwTfosMW6W2jLVs8PwtO/6fW0MpclzmAipokvcG3P+aW/mNX/nnvPftDzHTmXJzr9JFXOaJznMiJbh4ccC5c30yrUmtIckNORYNZDnkWqBFkfCjIcsFRkUEUZOz5zJyLfj4L17NT/3TfXTmeiTDvss8lQKLdta11QijEdYgdA46wYrcTTtQ5FlCc8eQn/k3h/kn//x6ugPN0TMaEbSwskWSKxItiE3xzgVJZolz9x5lhgzDILFcvphAnCEDi7A5ghyXqlSmXZU5ZO6vX6psS4YCjzf9V+GcbZHsUL7qzFT3Dipc1kKhEuvlblhFNJNRsyry5nVhsJYwCkjjmJ0Hr+bDP/J+3nD33Ry++nq2bd/Gp//XHzMcaRoNVaUs58ISNgK6yxmvvNzllru3IYQkzSyi4BippNuJhKIYwAVYtDZYowjDBv31hH53xHs/OIeazvn0r58mXoNGs+mQCghjq2SC4gcXDAhCstGQaCHlZ/71Id7/vkVOn1xBZ9BsNMhcCA8hDdZaMo1DW+HyIZ2/r3NoRJL1jZwTx7qARgQCtNdmZ4x7x32dMb/HetipeE14plKZuLCFDya80wt8v0oV5/gPZfWhj9RqsMVFtYzLExO24NYsoaEkU7MzjAYJ3/zG3/Lcc0fJ0yIbVLstRFu8nYiWHH2+z+qaRYQhaSbIjSDTjrMzBFpIUm1JMkBIhDJkaIaZxsiQUS45dXqD1799hnf+9A607JPGI5AGa1OMTTEmxZoUS4Y1OUiLiWNk2OOH/9Fe3vHubRx7aYPRKELKFrGGXIBUFhFAbi2pNWQWcitIDaS5wBhBri1SSVZXM04f7+PKiEp0eKUvY/B9dfC7HLIJ3AvHaGOs5x20W11I2DJ5wQ0SWC99prxbbQRUhWa+WPdTW4soXJYlNKemOf3yK/zLX/lN/t47HuTTf/wFTh4/SmN+FqkEukytLXSTMYagEfLikQGnzw659rZptzFiJEZYhKbYAtOoICDuZ1weaXZsj4jChG6akucRyIB4ZEgub/Dady5w8rkRT/zZGq35GcDp1woSBlASm+dkgx5v+pldvPVdC5w9uUo2bNCKFMM0QwU5nYZhkEnOXhgy1TDMzTWJE12jzjoBYYoWW5fPpVw6myLDVoEYv8bp1USzhxsH9HHEl/9NVraOy4Pqcy1A3H3l2K/UFYRewItyY3wyA6MkAn+skxiCLIkJFuZ4+eQZfvuTn2JpbYnmtoUq39xS7GwhKMP+7XbE0mXDsZf6ZMZAJMmMdPvRVpBbSZrDKE2I2k0ef37An37pIt2BJQhzBkmffpySG8XGmkTIlPves8j260JG3X6ReWKd6La5Q7wUpBtd9t7T4c3vP8ion7K25oh6mMXEOkYFhl7c5NN/ss4ff2EdG0yTiYAsBy0EubVoK8hyi1AwzCynTvTI10FNNYvEixArQhBePN3j8lKzljB9NT3s4bL4WCFi07CShcuXrNV3La7LC04OLlNSfQuxnuS4eDI4d0kGgoHWpMa5JdbW412xnAIVYJGoQBFYxbOPD1leygg7lswYJ8oNpLkhNdCPNX3d5467Z3nhyS7/+f86yeWlkCCE3rBPP87IbcDlyzE7r5Pc/vAMMsoxeY5Qsl6rCsgHKWrWcM8P7WP7LsnlSyMQIYMsJ9YZoZKsbTT4nf/7BI999SLveGgfC9tDlldjbKFSMpz+zoxGNWBlI+PYC30wIUIFzgIXEYgAi3JcPpFNKrx3ja1xghAeTsrNp7INSoWJyexcD/Vjuf2TZaCWwiosML/peIn3KvAyMXFj0SZ3FYzeNV3ts3u72h8FMkAjaU93eObplOee67kqWQWZhlwbUqNJtEYLxcpagoqGvOeDB7h4POG3f/lFzp4WNCIYDvskmSFJJIN+wo33z7LvrpB0OEQIhRUCKwOsUOg44fAbtnPLPXOsLg2xNnA2QaqJgpALZyW/+++O8dw3L/H3PnyYG68POH16HakUiTFk2lWYZNoBOogCTh0bceKFEWqqXd9LFmW/VYLkOCIp8T7GN9b7N8ZLY5K8TDSpxLzdCtuWql50svXDGLWwxfES08Ldrb53aTgUlkXhXYgiKbCqdRYOwcjIAUMFGGsRgcIOBI9+o8eVZUPYhlhnpBqSVBNnGcNRRk7I+Qsxcwsp7/q5a1i5MuL3f/UIx1+GqBPQHw3ItWRjwzK1IDl8zyxiVpFrCzIEFWFTjVyQHLpvgc6cpT9I0QJSnRM1Gjz3fM7/8ysvcPzRy7zxw9dz051Njp9aQduAUWqIU0uSC5JcMEo1KgpZWZM88c0N0ssCETTQulinUNiSsIXy4OHy6Cp4+URgS+4t4TnOiFu9aqz5de3ur0R4u1nlPTyRXv31zyzR6okcMcbZcox7ZRFGdO8C4a52sz5fCpcWZA2d6Smee2zI84+uY2VOqjJGWUaca0ZpxjDPGCYGo1qcvTRi7yHBWz56LYPVhM/95lGOHc1ozEjSPCHLIElz9t4yxa5rGs4bCBoIFaJTzVV3zXD1HdMM+gkaV8MVtts89UTCH/7Gc2ycWOe2D1/LbW/ocPbcMv2RZZho+nHGMMmJk5xRkpFhEKHixce6PP+NPqo5jc0s2JxSvEppEdJFAoVyUs0W3O5EewE/O6lAS4T5uChKgygCWyVhlAEP3+8qdKesEe2X+tRWo+9mbdpLqU+o3gJflBdUWyBcSkfJUjm9lcU5ZpShRyl5PyEejEj6CQhBkEq+9RfLnD6VELVhkOfEKaSZJcu04/IUjGxxaWXAoTsb3P6DBxhcHPHV3z/NhROa9qxA25RB3zC1PWTxcAvQSJyKIbLsuGGGmZmAYTfHZIZGu82zT4/40u8dIb7Y5/oPXMPr3jHH2nKX3kAwSiX9kWaUWOLUMEos/aGhGTQ595LmkT++gOlZlAwRgww1yKGfQT/G9nPsMMFkiae3y2oQ97dEXg1Xn7vK96RYF55x7Onu0nwTTt4GJc4qWhpzpzxLe/IlJl352ioc681V3c29pZSQp0jR4t4H38/V195MGmf01lYZdq9w6vhzLC2fZWZmkVPPLvHVP7zCwz+9BxVJukOLQKEzV72RW7elmZmIfjLipjfNsHp2D6e+dYnv/vESD/7MHuYWJRvrmpnpkG0HI8SURKcaYYC5BrN7mxijGY5y5mfavHw04WufOs7wbI/9b7+Ku96xyHq3RzLQRI2QfpwTKEGQgwpcdsxsK2K4EvLNPznLuedTok4HZTrccc/7OXjV1Wz0VzDaoKQg1TFnLj7NiVOPoqKgilaOlzeNcVINXp+5yrLl+oQaf1sQihUQlK0YJzPL/TymSS8OCh2+xe6KLx1KLq/cA+t6nfTWr/DaN/8YH/vH/5q5+Ta9NVcGM71NcuLYWX773/wrzp54nPltO3jyz88TtgLe8KN7IEgZDjUSiXO7XQoQStGPM6bnLYcemGP5/JDzj67zxK42D/zYNpTKSWNNsxPSjGA00EgUQSQJW5LhICYI4MLlhG/+6Sl6L27QvH6Wa940jxUp3bWEqBGRJBohJNZKtBbYVNOKApRu8N2/Wuapr6zS6HRIegMOXX0j73v3P2Tv7g5xmhIpQZpC1Gpw5NhR/tsnf4FBegIVtTG5LuBssJgasV4jX/dd1HGPMZz4iC2PlGRQHytqvTwE1tgee5U5ZcVAL3xX01bd6La4ZZmIV/ymgoA8ixGNbbz+re9EihZnT/TQxqK14ez5ETt27eSW176XkyeOYuIRM51tfO9zF5Ftyd0P74I8JoldUzstjEtNkkAQ0h/m7L6lxb5XZnjxSs6Rb60wtbfBHQ9Mg8hBueYAZf2WNNb9jSzr64Lvfe0ylx9bg9mIA/ctsmNfxNpyD6EKw66oCtHWJf83FYR0+M5fd3nsTy4Shk3SdEgrWOCuW99DoznFK8ev0GoJAiHIM4lE0lG7ufbq+3jyhZeI2lEVyq0A9XeZX5WV7g+rmW0M6VUiosNNlRZZ6YAyJXjC8pv8XimJ2qrzDhb63erCqCisQRWQ9Lvs23c7e6/ah9GCPHfVHtoGGNPk8sU+r3/7e7j9/vezsZxCbumoKb776VN893MXCMImsilIjCZHYKQkNwakC1nqwLLzjimmrmnBKOf7X77CS8/GtDshWqduXlqDcflr6SgliiSvvLDGS39zBTLLjjtmuObuaZJBQp5JlwWrXWg0N4JhnKMkBLT49p+v8NgfnCSQAUbk2KHgnns+xJ13/wBLSxtkacBgENHvNYhHEaOBJhKzXH3wXiRz2CwtjFav3Vblg43jb8zdGlOxkxLWIcwnGYtwNXR27ISx8RWiHMK9oAt20z3qRF9RVWOSZ1iTIYXBmow8h1vueIC5md0M+wlZLkgyiGPQVtEbCuJE854P/QK3vuljdLsCYSM6wQJP/skFvvHfz5OstIjaU2gr0blFI8iRWBmw3kuZO9Bm120d6CiyczHPfnmJSxcNrY4qkvpcwCdLNVIpLq7kvPSdNTgT0zjU4uAbt9FoGbq9BJQk0QZtQacaq6HdmGHtTIuv/o/LHP3KZcKog7CgN1Luec1P8463fILuxoBkaMhSyXAAw6FllMAoyckzy/59t3DNwdeh0wGNMEBaUKLsyljEICpDWYwzmJ3EhQd/SxU3r5HjxgWbnDc/EFZJjXKf2xPc1htXfCn3Z3WcorMUpVq0prfh0v9T0t46zek93HjLXSAiut0R2gakmSXPDbmRGCPZWIXmtOV9P/kLBM05vv/136IVjWhHM5z89iWWTve5/u0H2XvHFEE7w6Qj8jTHWkGaSYTV7L1jgZVjA9aeG7F2pMfRv1lh4UCnqOk2iEDCKGVjBda/02f58VVoRVz1+p3s3N9ibWUDI2SdsWMgUE2SXpPj3+tz9rFLmEFOc6qJGfYwCTzwpn/C2972CeKeZrDheqjZomBPU7hd1tLrDml3ruLQ/vs4cezLNFquLi5NctJRHxEGiMAigwgjdJWrN45YW+GmCnHbUrXbymf39jJdv3Eqzi5ZVIwhvdzKLG0yp499+eJ0WRgI0v6IufkbufmOh7nprnvZf+0B/vB//Duef/xr5IMRd977IDsP3kA3NgwTp3uzHPJcOO6xAhVa+j2FDCzv/tBH2bl7D9/5/H8k7h2lMTVNfCHnqc++zAvfnmLPnfPsv2GK6YWQ3GqUlvR6KTMzIVc/sIvh0mWSE+uc+u4G3cuOe5AaK1y059QTG5iNLnQN29+4i+tvm0PlCUq2iJSrGU4zWF+2XD6VsPzCFbLVjCiIUFIxWhvSaR3mTe/5Je563fvo9VKSWCFCg0501SXBIMAYpBSYPCPSHfbvv4dd229hefkZFrcf4sGHPoLpBxx74XlWukdZ7j2PbCk3X59JSw9nIv17nB48zi2IZaJIQEwMZkIc+EGUOtlYWJChwqRdlNzJD37k17n+lnsJA8s3vv4ZXnrhCYTIoLOTm+96C0G4yMbGEJ0JV/1hwJiifQQCbUApickF3aWUW+57B9uvuppHv/jfOXX0i1jZQ0UhyfmY4+fPcvJrgs7eNvMHppjd3kaFIZmCVjjN7I4RV87FdC9b0mxIlkmQrrxGSMna0T6mn8D8AkFjltPfz+l2M8KwjdGGQTejv9onWxphhhYhFSqQ5N0BOlHccPMPcf8Dv8jiwiG6yzkmdzFobVwJv7VFenDJgcYZeINhwlTzavZcdT+Xl59ibf0iR154lnc9+GvcfviDEK/wF9/4Tzx17P+jMTdHrvNC0nplfX4umY+jKlnN1oyLrbNLJ7NWx2urykPFwTKzpTLuXBH8YK3HdTe9n5ntBxgawbPf/i6P/Pl/J02X0Tpmz+EH2XP1jYwyGMUCYVzFiNs0KaRHsUVYtlHRFpK1jM7CIR78iX/PxWPv4rm/+Z+cP/1tDD1kowFY+kf79J/vci4ULkhhHTKFiQg6baw25H1ZuDnWBTSsxPRBiDYqarL0zDKXn0px8QWBzYtJSIVUrl+b6Q/Jc9hz1eu57/6Pc801b0InEb2eLlqE2KJvi9sBs7rIQhECMFgjsVKQZgntxgx79r+e5176LFJu8PLLXyASu3j4/n/BbLiL265/J0+9/KdgMpCBl8Pmv8TEt1fzzyvOLjXAeM+xLXna/2C9YxbINYs7ryNQUyQJvPz81+munyKaNuhYcO2N9xI0drC+mpKnokJo2ZvFeq6CMbZIiHCIzwcaKyW7D72RXQdey/Kp4zz/1Jc4eexLpKuvoJoWEYVYbTFZ4SZlGhkkqDBwCRVxjtWm2jGyhY8uENg4dapJhUU1i3VPHtAaHY/IkxFCNNm55/Xcfe9Pc/i6NxHJDqMeaJMgMK63j3EVIk7Xe4jWZdTLZcbo3BCPDPMzB9mx/TauXPoqUTPh3Pm/5uLyD9LZcyMznV1siw6zGj9K2NlBnmeUAZWJFN7qZYWXN1jxpgNqXSSwhaHmwd47NuZjObQHijwdQLCDfYduozU9xcXz51g69yyiYbB5Snt2P3sP3IGlzXAwQkGRMSqKfp2iokJrynop4XqWGUeMOjP04xxpQ2b33swb9t/Aa7o/ydGn/5IjT3yK4eoRVFsgVeB6pwSOm3SeuiCPoAhauFLasi9lGWK0ALlBKEkgBflgAx1rpmeuZf/Nb+HwTW9nz647ELTpDizKxAgrXWdTYUGLcW/FWpdAqiuXB1sEgixA0qXd3MG+vW/g3Nmv02wahsk5Lm88z4Hd19JsLLD3qrtZOflNxDRVa5GxV/VILuu74WN8WTJkUJ80jsIxG8C/wQT2rbXOUNlYY/eetzO3eIDMwKlXXqC7dBKhIY9HXHPd65ibP8xwPUGnBhE4ypfGIVng6r5MCRerHMJ10ejVFOIRSZqnxEODFAFRtMBd9/8Et931Ll549os8/eh/I1k5gmq1sFK55EYjEFK5ujBD7dJYAblnjwqLlCFSpyQbq2zf/UbueuAX2bn3VqJiB6vb02RZWpXTKFG2snXWa7GlgeseYBwhOy/XNesp8sasleQ6paWn2bl4K+3GPtLsBCZd5cLFJ0kOv5NItjmw5zaePdHC5lmRLeQ3urOVdK0RMkELHsoC/7wtXO0xqb3FYccxxkKWc/DGtxDM7CIdWc6/8hSjpEcQQC7muPqGt9Fq7yAbjlC22CARQWVNmtxgcqdxXAO6DKOtIwAKS904g1FbAUiyLCNNEnpdSRQtcNNrPsTBm9/G9772SU4/9UmkGiKiBkKV7alcDxZjC4QL5dSXcLpOihCbbJCOLHe87ld57f1/nzQPGAwN/TxF50nR2F0WPUwFubFIq1HCFn3PICj2AISMKLssm+IpOsKCQWO1RWeWRKe05B62zd3IufMvEUYN1jeOEsfLtIKd7Fm8jtn2zXRHRwnbC+Q6fRXDbAJpW0j58c4LlQU+jvtJKe9fUCqJTvogF9m+9xaUnGbl4mVWzj0JWZc87rHnhney87bX0rOKXj8lNymZzjCYos2lRMkGgQpQKgAkMmhA4FovmNyQZ46jjLFYXQAOhTHKpRUnOcOBoNHZxUPv/WWe2nED3//qv0ek56HZLtSPdEkElXVZJkRZpIzQozVkPsWbH/5lbr37Q6xcydz2pTHFw40UQhhEZhAmRwtBoCQyChFBhJQBRmkSm5LnQ5K8T5rE5HmMMQZhBEJEhGGHRjhNq9WBQDE/tY9DN7yRs+e/jDUpG2vHuHjpCIv79zAVbefqHXfy9KknkZ3FYjtzK5acLMcvcOfp9qAmgq0oRXhUYutDnoyXSpFu9Fjc+TbaM/toRJbTrzzG+toxZNNg8za5hL/92v+id+kyw/Wz6KyPMQnWmnorVCqkCgnCFipsEbVnaLQX6UzvZ37hBmbmD9HqTBMKi0kSktj1LCsrw1VhdCX9lPVM8prXfhCpmjz517+KyC8gmtOOowlwfVFk0S1CI1QDO+qj8h3c/9CvcNMdP8al8yvkeYpGOo8ht0UxgyAKAxodhQotqc5ZGlyht3SG/vordLsnSUYXSZMN8nwINit0dAE4qVCiTdiYp9naTjPcTrMzR7//AkG7g9Ep/dEljl94jJsOPkwk59i78zU8feqzYN1GTNXl0WPUyuQpcFXucfviONiM6BqRWxSj1E+TqfxGsFqz/9CbiNo7GWwITh99hKR3FtkUiCBi9cxTrJx6onhCniwm42df6MpCRhjQBmNdUxuhGig5R2vqKmYXrmfbnluZ2XEz0wvXMDU1Q5alxL0Ek0MQuJKcNM1ZXRpwx53vJ01Xee6R30BmCTJqFXAKC4PWIsII8gSdhNxz/y9yy00/xvKFdazJXXuv3CIyTSMIaMxOoyJJb3iBU5e+z9Klp1i//CLD/ll0uow1iWu0IwtDrUiWHPOJhca14pSuIY9xCRtSWocNKbAy4dLS91laO8+CuoqFqetpyavIkyVU1Ckyc+sCIJ8X62qegtctlGkQQWV5F0iuxXkVPqufMFcdK5CtQvJRF6n2suvAnTSa06xeOU53+VlQGUJOYXOLthol24TRNKo1g1QNlAgAVXTLT7EmweQJxmQupzsfkesBOovR2RmS3inWL32H88dayMY8zZmrmdt9D1cdfjc7996CsJbRxghyQ9RW5Dpn0Iu57TU/QW/1HKe+/ynCUDpiwjG3VS6zI+8NuP6Wj3P77R9lY33ojAOhyOKcMAqZ3TbLMOty6uSfceb4F1hfeYksXsbkI8epYeT2ApRypUU2IpCzNJoLhKpVW/w2x9oMYzJ0nmJ05mrD8sT1LM9jRMOxYLd3nDPLz7Owey/tcJFd87dxqvt5ovYURnsKdkzHei5UydkV6xclu35WkpPcng4QPgHUTrvbDIfRaIO9+95N1NpLnhounHqawcYZsBaTaKZ2P8D2qx6k1Z4maDQIm7NI2YTiWRu22G40MsXoGGsSdBaTpz1Gw2X6vQvE3Ytk8SomWcekfUx8he7wDL1L3+bi859ldtcbOHjrh9h/7b1IYRl2ewRKkqQZU50ZbrjnJ+heOsLale+hpmcxeV5YzoJ8Y4ndux/izrt+DqwkixNMbrBCMbMwR390icef+BRnX/kcyeBFcjNAhG2CxgxBczvYEGsVgiZBNE2zuY327CFmZq+j2VhAmaBo4GuQZECKFRojcowpuyKnKGAYX+D0uc8T919kmC9z7uLTXL/4JkLZ4eCeuzi5+nkoHidpbcmcVZSc+pcCX75FLfz97IJCqsE+0rdQ5y4glINtsOvgm7HMk8cZl888TzzqFr7sHAdv+yl273vAhVKVCx+mmSbLMvJcF1Z2SpYNybRCZwqbh+Q6IBES22gQzO9E5X1M3sem65CuorM1dLKOHiyzfOpzrF/8G04feSvX3f0xrjp0B3myTjrM6fX6bNtxHdfc9VM888h5dHIBghZCCnS8QSvaz013/iyzCwfYuLwMRtOZmSdTI77/3P/k5POfYdR7BqtyVKtNGCyCaGB1hKaFCtqEzTnCcI4wnCWKpomiOUwekhmBlgFKNZAiIgolUeBcPG0zshwwCkVOYBtEszewMTjJpe6LWBmzsXGUbrxEM5hmx/zNtIODpMkyImphcl3h0gt4VpU7tcntGWiMvSaDbSU3e1q9VD0yIB9tIMNrmF28FmlDht0+q8vPYm0PbMz0zn005uZZW3mO4dJLjEZLjEYbpKMeeTwkzzK0zsCmWJG6FlqmqEwUugp8SBUgynRcFSDCGWhMo6auIpgZYEaXSYcrLJ34MzYuPMbJa9/HDff8BNsXryLudxmudtl/6K2sXDjCyad/l7CVg1ToUcb+2z/AvqsfYuXyBlIbZnbs5NTFJ3nqb3+D9QvfxqgMMT1NEDRBtbGihZANhGwjREiZX5Ika8TDNSDH5blJsC7XThJCEBEEEaFs0WzO0ewsEobbCdQ2GkEbqSLC1jwz6U1cOhuBSRmMztIbXEJGTdrNHeyeu4njy18garQY6zTr22ETeWR+aW9QWXAeRmtqKJDtGVNlH1OpQtIkZvuee2g1dtNpNLm89BzD3ssgE6RqEXdP8Pxf/XNMMsTqAZoUK52lKIUoNu1tFYCwRccdaSzYDGvdtqW10uV7SwkyQqoGImoho2lQTcTsDqLOHDLukQ83uPjip1g99z323fERbrjjYWymIQ3Yfd27uXLmOyTr38cIQ2f2Tq667r2MRhIlobltmu89/RmOPf1fSLLTMBMhg23IYBqlwqLhT1FVYjYwOsfqDGN1Feo1FMEUcoTQjjGswaZuPxtjUeui6MDUQso5lJwlCuYIGgvE+UmEamLpEqfL9JIVpsJ9SGbYsfgaji/9RdFa2UUdRam3S/yJAkdbhFODcd6dkNeTxpvwxhqDtQG7rrofxRwIOHf6WwwH55GBQgYRVvfQo557aEvV4Qj33MssdcCymqrjOwCKWkBJoEw7rokPskK8RKhGB9maRjSmEI0OjWYbmXRJBi9x/Hv/lo1Lj3PT6z/OzNQ8Um2jM3eYwZUnAcPcntsJgx1ELcGAHn/7td/m7LE/wAYjgnYDISKktdiki85GZPkIo3UR/nQlPRDVHfX9nDyROy63Odis+OseZWCURAS4hnj5RXJC0qFw0TaRI5Vr/TUcXeHS+jF2Tt2NzhQLs9ch2Uae9hBRB5vZOtXcD5VuGf0qG9V6wVRf1TP2uawOFIggRKcDmq1rWNh9EzJqkmUjNlafA+G4mlxjybG4VhXGAiYC1UaG22k252hEC0SNBZrNWaLWFGGzTRh0CGwDqySoAFm4J8ZYtE7ReZ80XidOV4gHqwz7qwx7y5hejywYEDQbBJ2IzrZZ0uGQK6/8Mdlog7ve8X8QTU+TpImTElYShtMs7t7JytrLPPqVX2Xl4pdRHYVCoPsD8mwdjEKIecJwB+32Iq3WTlqtnUSNRRrRDFHUIogUURAgRQDCYEyONhpDhtExxqTkOiFLh8TxCsPBRXqDiySjFbJ0HUwfSJ0RpyRCNlFBA50PWOs+SWrfhQwaNFs7mJ++gZXh36AaU9QZqZ6vVBKcx+klerfMVJns2eF+FJRJ/TKQpN0he3e/lma4G6kiNlZeorf8AjYfkuezIOdotnbSntpB1Fqg09lDe+oAzfZeGuE8YdBGyDbQhMw1p3WN4hWiaAKLMijX89XFuqxxolGkWJlhRYbJB8TJKv3BOVZXX2a9e4Lh+irYEUGzRWMqYO3M/+aFRxpcfc9HaLQt1iaAojUXcHnpMR77yq+zcfkbqKZCj0YYM0O7cw1TiweY7tzIVOsaGo3tNJozBKpFIBsEQYRSIYGUqEgQyABBUBQuWqxxPR6Mdlt72hqUEshAY2VMkncZ9Ffo9y7SG5yhu3GWfv88/dEl0mQF9CrYhKXLj7K0/RV277gZTIdtizexcvLrzmUtLOvxTODCOp8ItQkKP9s3wMoIzLilVpKIcDERo7E2Yufeh1AsIDCMess0mncyd/0b2L37blqdvSg1g4o6qKgFeYN0CDqxKKFQKOeyiJAgUq6uq+yhoaXztylFoUHgqh8lbrMiDAVBEBFErigwyWL27t4gya6w0T3F2torLC0dYTA4RtiIWD75FUajy+TZRtGeXHL6xP/mzCt/yWD1CBCjsz3s2vVu9my/n6n2HlRzGmmmCWwLFYXIkILzLJAjSN0mjdYYrRDSZa+4B78qQhmilEKICISC3KDTBE0bzBxBuI+p+VvZuZDB/iGaAaN8jThZYTQ8z8rSc8SrOXGaMYwTEBGzc9eDbDm1IALqrgI+nuw4ZxecL8K5g7ZMDfbPmezdQZH9KFWAjlfphLfw2vt+h2b7ILndQLCGkQYZzCLyGYwuOvITuCfXogmVQZGj0z5Z3iPLNsjTIXnSI003SPOu+57GaJOgbeoSBK3T3UKECClcbxMVEYXTNJsLNBu7mOnsY2pmL2EzwqqYzPbo9i5w4vi3OH3yS+TDVwiaTUQksXqAtQZtcoh7YA3TCw9w4/UfZWHmNTT0AkoKcjXE2ow0XqGfnKWfXCGON8jSPrkeYU2Othpjyt6mboNHygAVRASqQSCaNKLtdDq7aEUzhKJBI2oTBlM0mlMuJq8z8jwmNzmpTlxzocCgVUzWSyBuEtmIdrPDenaC7zz9z0jMOWQ0jdV63AAvDbTK5hJbiPFJqT2p5Ys4qxSCNInZvfdtNOUOTLxBblKane2ErSkyE5LlGcZskMTLxKMNhvEqJl1D56vo7DKjwRni0SXybANrYnQ2QpsYbdLCsnUtNOouikVdmChrxaTrIixClGiiaBNFO2i2DzI/dy3bth2mPX+I6elbuOPO69i540aOPPMp1le+RSQNBAEmiyEdgm2zZ/97ufbqDzMVXEsoWqTZeS5tHGV9dIb+8BTJ6Axxeoks76N1UjxY3RSeSaniSl0pQBaFekKALTZ5ZJNQNZBB09klUYdmtI1O8wAznYO0GrM0ow5h2CGSbRfEy0IEM2hSdJ6RpBmdxi4XTbvwCqI557ZwX+VVBlfKZAYRzh1wgrvwv6rozBiunb4WMsDmCcLO8/oHPsV063aM3SCxXeJkicFwiXhwkVH/HOnwHMnoAmmyjM66GD1C5yOMiLF25KxVa4vGcpL6cXKSupqEKp+66nQshMvaLL+Xj3C2CkxIIDpEagalppiZuZdrDvwAc/N72Oi+wMsvf5blpb/FihFWSNrN7ezd+Ua2z78ZZXYh5YgLS3/JpStfJ81HpGaAJgapXYtkJ/IK4JV/XZqRI0DhhZa9DhUY53VUsMTNWSuUaROpaULZJgyniKJ5mo29dNr76LR20Aq30Y7mCcQMQjRodCJWVr/AI4/9GjSly66y45mCVZTMUs/HTiYvWM8DqnR+bdsLBDodMbXtdvJgiSsrf8XqyjN0e0cYDs6Tp6vobAOTDTEyxrlI5Wa7hFDgmrg3KR9NXPUCq8RNHReylbsHwqttrohAls+2LjwKa9BmwFCvI1JN78yzTEWK2emfohXs47pDP8n+g28jS/sIo2hH2wn0AnkcMr19kUsbX+SVs59GB0OIWghT7kkH9TOwTfkQuQJIFVyd70z5pOKSYAviLZ807JZYAFoajI4Z2T6jPHfgGgBERKLliCCYIgoXicKDTHduZMf2Q4ggImzNkuarCNWA4oE1nhdWhdTGCgvCuQO2qtvyui5UFCFKy44iCS9FBXM0gnl0kpLoVXLbLQIJxaMSrEBIWfRKq3tyVpUtZbVCFZf1RYn/t7QZrIdsB1CBqNwM52sWiysS+6QEPVihHd3I3Xf/Gouzt7C+dNblsBuQWiKMIpBNWrOzXBn8Lc+/+JsM4hOoqRkHC+Oa4rhnyRlHUMb5yhQ1WSXihf/c6K3+esWO5XyrDgrCdXByIRkNVmN15u5pjLNZTETINhrhDCq0DLMLaFJX7z2hgislbEv7zY4j239GcwHeifISUX0yJsdkGQiFCBQo4XxMSz1B4/Su29UyHuJKetrs/Ze9RuwYkMpjXoUEtZgv6Uf41y06/0th0MOUuenXcfjQh1iYvQ4hpGsYawRhpMhEwrmlb3H8+GeJ0/Oo9pSbry9lqqeemyowYl3BGOUmY1WqzOSrJlovW2xsDVsNl5Uodi25rMnc1q92hKKCsGgTWqiWcv0lsm0N4UpIB7P7K1lUVnQW2tsjmHKSojLehHJi2BStsEqA2DJl1NcJpU4p1VX1/5h/ABXAPAh4CN4Ckt5F3XUsuipQFAKk1eSDlEa4m/nFe5lbvJVGayfCpIxGp1hdfYbV9efRYoAKW5jiwee1iWqKXmxet4ny0ZEaqucp/h2Rq3r+Y6Cf+LvFWRPM4UChHMOVBFnFwwqoVgkNHtOW1wtmD4xFVWoOh02IwKMcRLX48pGB/t1LyiqvW06g+s1OItQHTEFsPoWIceqtpZOte6zbov+nNdWdBCBshs5GkElkuEAYTmOB1PbB9CCIkFGrmr4VorhmPe86mcMW9zF4i6n+bJXw4e8kVoQ0ieMtRLG/7jL2LgoAuD43ojIb8Fdcwqdi2wJOauZAYcDZqrwHj8NLfE/uateTLG9iJ/pj19Re05i3rskHvIyt1z/m9+WzNQF4yKg+T0gU6+lWKQCTO99aGxABBA1U2MDiarKc3i1UMjV3sNVfP2Hf23Sw3rqBmhDH1lYUPW5CMOM/eNQ9KfLHSGqsNHfzmJLYnKKtqgWLw8WDPcbvPwFY7yWLn8Z/FeXjrRDe6i2el4VPAoxx+Fbaz6OcamG1BKrVQtVgAAEFIl1gJ0IFTQiFIyJLgfhiFsYHkLvupNCdXOUEXdfMOzasFt9lK6ty3T4Tjw0fu8Zmke8rWTuB6HF5LCpaqIoESjFT+mxVW+rqamPSvv4jKCjP1nplYuK+MKkfa1FvstfiRnj4LKnBX0k5t0kgeF+r8bZCqI8AaxxXCmHqNpO20KfWjF2iXFd9KzGOgKKe2XpfxxFSL6Hs4Dw2H++cLTV36QnBeCFlvdh6noVH43cn9i+DhUAWT5vbgjWrp+WVuyt+F49qvHW/2/JmFaqKWmEBonzwGTiXrECuqYDjuXn4nL8Z+VWJkPVo0AsiiKJpe/n4dgpAV0gsNhCcxC+t6FIFFQ9fM+VlJ/VvyTOmJjgxjrytsCYKcTbGxb7IqDY1yp/qC7nSqHK93kmb0ofE+MfyaxUsK0p2iwqcSrCMlbB4nFc8gZS6y9oE4kURKJmkMFEUypWfy8d2T8KSzbrJX1BZsOcWU3CoLIBFmUxYqiMfIFthYKsJ2LrGefJwdalNk96svsrf/eQ+X7ba8RnVLSknLibGR22+TbFGXxNS2+Hj3yGwheiSwkeyd29TRsAKK7R4riTWFjs6tZjWVhcJhBJRvGvdL4oHwZScLEBJ98gnpYrnemQVVQohkUEd4LPGoHVepCoVIKqmawrmHjfmJl8VZ42pgZKgi2uKsqSqFCm1zhoj0Iom6+7Am28r/eGb1Xg5SngauDSA/NcWot4WBQ5l/9eqa/QkYXkfq+zSSoxWARY7rl9KhJtiAwCBiYf4jwIWQeiyMMqAgyinWLdvrEKIQBQExIMedtiDqENjegaLwVqByXOyYa8ytoRSyLDl9Kwt93DdA1uQJcf7CrSM6JWcDpuVZgk5W6+/evnlsZslQ4noCumTtDVWaeONw3kU1j82cY4ngyd+33o+48upidMlfUJpzbtnhPib3JMcXsHHUj5s1QI2dU/CVSp0lzeuv6jDgS02DkrdWRBEKfqVS0YYrV5h77693Hjd63jl5BlOnT5No9PGWEUkBfM7Fyq+SXJNb5CTFxsQFlm02VXjqqUgSoe8gkdt2aLVAwRQuk/+YxbqpW9WASWyxkVEaRvY6rdNUrv0lYvzN3E540TxKjpu81xsTQfl3IRhzEb1wxlj1vg4ARVEUOh0x60aJCgUad7l5z/xS9x7z50MRyNajSaf/sxneeRrX8eGAUKpyoVzYtlVPQgBSinS7ir3vf4efumffYJbb72V4ydO8R//w3/kW999DJ0kvPa+B/gHP//TzM12EELyzHMv8nu/8/ucu7JKY6pDlpdPky85tQCvtMXjBsA3HGz1fw3qcRRYtkJwef5WrzIJ030pz5HVFewYd7PpuH+lCub4RCW8+Va/bjGlcj3ucyVpfKGGLXe9imF2EuFl4ZvG8ZILUAgZkpLyzofewrvf81A1+vlnn+WRr36FMtnAwbwsVTHIQs9LHZMnA/7hL/wcP/QDDwOGw1fvZdDf4Ngrxzlz4gV27VjgPe94kLm5aQAWZmf59O9/CmsSpJxC+HquFN2yXGG5SkfaTmyWTrTFkX+JIL/MaStkb82Dm45b6VnUYoyQfHRMat4637uA9bjyLuZbxsC9+3lJCWOzGbMlvCiGLTsv+JwxuURPzdXE6yju8pUlsjSj2x8wM92hN+xT+hhVgRmCSv+Z0g5w95pZcIjsbfSZnp1hYdsCnU4HCFjv9rlw+QpTUy0AllbXSLOs6tKA9URoOafy2rIUVU5vOylf2yGlIK3bSxUE6YniMRhMANlxiyyIqLBBhEudqqOBW8T4S/xU+89eB3DhGXqVQLKVHeWjo55UOd+SaMsjJcb97LTJhENRX2Ls5c3ZtzxVGBJEIWEUEoah225DUFnFnvEGLk3Jao0IGwgJn/7MH3HjdTdx9cG9LK91+V9/+HlOn7sMagZjJYGSBIVFLgNnG1hjESikshSJa0V7i5y6xWaNQIREYirmL7co3Y4chYeg3LlF/5OS+CujTbrt1erStkhGMLg5iEpvOGOo1JnFtqfFFnsFReWolJQdnwWyDmAJKntJlGK8YhpbJMWUzFMQbuEZ+YLc3zKuSFfA/w+rmcXQKBLktwAAAABJRU5ErkJggg==" alt="Library">
                <div class="brand">Library</div>
                <div class="brand-sub">FOCUS DASHBOARD</div>
            </div>

            <nav class="sidebar-nav">
                <a class="active" href="dashboard.php">
                    <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9 21v-6h6v6"/></svg></span>
                    Dashboard
                </a>
                <a href="book-seat.php">
                    <span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg></span>
                    Book Seat
                </a>
                <a href="my-seat.php">
                    <span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="6" y="5" width="12" height="10" rx="2"/><path d="M4 19h16M8 15v4M16 15v4"/></svg></span>
                    My Seat
                </a>
                <a href="subscription.php">
                    <span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h4"/></svg></span>
                    Subscription
                </a>
                <a href="usage-analytics.php">
                    <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M5 20V11M12 20V5M19 20V8"/></svg></span>
                    Usage Analytics
                </a>
                <a href="diary.php">
                    <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M6 3h10a2 2 0 0 1 2 2v16H8a2 2 0 0 1-2-2z"/><path d="M6 19a2 2 0 0 0 2 2M10 8h5M10 12h5"/></svg></span>
                    Diary
                </a>
            </nav>

            <div class="theme-area">
                <div class="theme-title">Theme</div>
                <button class="mode-toggle" type="button" id="modeToggle">Theme: Glass</button>
                <div class="mode-menu" id="modeMenu">
                    <button class="mode-option" type="button" data-mode="dark">Dark</button>
                    <button class="mode-option" type="button" data-mode="light">Light</button>
                    <button class="mode-option" type="button" data-mode="glass">Glass</button>
                    <button class="mode-option" type="button" data-mode="sunset">Sunset</button>
                </div>
            </div>
        </div>

        <div class="sidebar-footer">
            <a href="../logout.php">
                <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M13 5V3h8v18h-8v-2"/></svg></span>
                Logout
            </a>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="welcome-wrap">
                <a class="profile-avatar-link" href="profile.php" title="Open your profile" aria-label="Open your profile">
                    <?php if ($userProfilePic !== ''): ?>
                        <img src="../<?= htmlspecialchars($userProfilePic) ?>" alt="Profile photo">
                    <?php else: ?>
                        <span class="profile-avatar-fallback"><?= htmlspecialchars($userInitial) ?></span>
                    <?php endif; ?>
                </a>
                <div>
                    <h1>Welcome, <?= htmlspecialchars($userName) ?></h1>
                    <a class="profile-mini-link" href="profile.php">View / Edit Profile</a>
                </div>
            </div>

            <div class="timer-wrap">
                <div class="timer-box">
                    <div class="timer-content">
                        <div class="timer-line">
                            <span class="timer-clock-icon">
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2"/></svg>
                            </span>
                            <span class="timer" id="liveTimer">00:00:00</span>
                        </div>
                        <div class="timer-sub"><span class="moon">☾</span><span id="currentClock">--:--</span></div>
                    </div>
                </div>
                <button class="btn btn-green" id="entryBtn" <?= !$entryAllowed ? 'disabled' : '' ?>>↪ Entry</button>
                <button class="btn btn-red" id="exitBtn" disabled>↪ Exit</button>
            </div>
        </header>

        <section class="stats">
            <div class="card">
                <div class="stat-icon stat-purple"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2"/></svg></div>
                <div class="stat-copy"><span>Today's Hours</span><strong><?= htmlspecialchars($todayHours) ?></strong><div class="stat-unit">Hours</div></div>
            </div>
            <div class="card">
                <div class="stat-icon stat-blue"><svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg></div>
                <div class="stat-copy"><span>This Month</span><strong><?= htmlspecialchars($monthlyHours) ?></strong><div class="stat-unit">Hours</div></div>
            </div>
            <div class="card">
                <div class="stat-icon stat-green"><svg viewBox="0 0 24 24"><path d="m4 8 8-4 8 4-8 4z"/><path d="m4 12 8 4 8-4"/><path d="m4 16 8 4 8-4"/></svg></div>
                <div class="stat-copy"><span>Current Plan</span><strong><?= htmlspecialchars($sub['seat_type'] ?? 'None') ?></strong></div>
            </div>
            <div class="card">
                <div class="stat-icon stat-orange"><svg viewBox="0 0 24 24"><rect x="6" y="5" width="12" height="10" rx="2"/><path d="M4 19h16M8 15v4M16 15v4"/></svg></div>
                <div class="stat-copy"><span>Active Seat</span><strong><?= htmlspecialchars($activeSeat['seat_no'] ?? 'Not booked') ?></strong></div>
            </div>
        </section>

        <section class="middle">
            <div class="card">
                <h3>Seat Booking</h3>
                <a id="seatPreview" href="book-seat.php" aria-label="Open Book Seat page">
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
                </a>
            </div>

            <div class="card calendar-card">
                <div class="calendar-head">
                    <div class="calendar-title">
                        <strong>Calendar</strong>
                        <span id="calendarMonthLabel">This month overview</span>
                    </div>
                    <div class="calendar-chip">▣ &nbsp; Study Days</div>
                </div>
                <div class="calendar-weekdays">
                    <span>MON</span><span>TUE</span><span>WED</span><span>THU</span><span>FRI</span><span>SAT</span><span>SUN</span>
                </div>
                <div id="calendar"></div>
            </div>
        </section>

        <section class="bottom">
            <div class="card chart-card">
                <h3>Weekly Usage</h3>
                <canvas id="weeklyChart"></canvas>
            </div>

            <div class="card diary-card">
                <div class="diary-head">
                    <div class="diary-title"><strong>Today's Diary</strong></div>
                    <div class="diary-date"><?= htmlspecialchars(date("d M Y")) ?></div>
                </div>
                <textarea class="diary-box" id="diaryBox" placeholder="Write a few notes about today..."><?= htmlspecialchars($diaryEntry['content'] ?? '') ?></textarea>
                <div class="actions">
                    <button class="btn btn-green" id="saveDiaryBtn">Save Note</button>
                    <span class="status-text" id="diaryStatus"></span>
                </div>
            </div>

            <div class="card">
                <h3>Quick Actions</h3>
                <div class="actions quick-actions" style="justify-content:flex-start;margin-top:14px;">
                    <a class="link-btn" href="my-seat.php">▣&nbsp; View My Seat</a>
                    <a class="link-btn" href="usage-analytics.php">▥&nbsp; Open Analytics</a>
                    <a class="link-btn" href="subscription.php">▤&nbsp; My Subscription</a>
                </div>
            </div>
        </section>
    </main>
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

<div id="shiftModal" class="shiftModal">
    <div class="shiftCard">
        <h2>Choose Your Shift</h2>
        <p id="shiftSeatLabel" style="color:var(--muted);margin-top:6px;"></p>
        <div class="shiftOptions">
            <button type="button" class="shiftOption" id="morningShiftBtn">
                <strong>🌅 Morning</strong>
                <span>6:00 AM – 12:00 PM</span>
                <span class="shiftState" id="morningShiftState">Available</span>
            </button>
            <button type="button" class="shiftOption" id="eveningShiftBtn">
                <strong>🌆 Evening</strong>
                <span>12:00 PM – 6:00 PM</span>
                <span class="shiftState" id="eveningShiftState">Available</span>
            </button>
        </div>
        <button type="button" class="shiftCancel" onclick="closeShiftModal()">Cancel</button>
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

function updateCurrentClock() {
    const el = document.getElementById("currentClock");
    if (!el) return;
    el.textContent = new Date().toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit"
    });
}
function startTimer(startTime) {
    function update() {
        const now = new Date().getTime();
        const start = new Date(startTime).getTime();
        const diff = Math.max(0, now - start);
        const totalSeconds = Math.floor(diff / 1000);
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        liveTimer.textContent =
            String(hours).padStart(2, "0") + ":" +
            String(minutes).padStart(2, "0") + ":" +
            String(seconds).padStart(2, "0");
    }
    update();
    window.timerInterval = setInterval(update, 1000);
}
updateCurrentClock();
setInterval(updateCurrentClock, 1000);

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
    if (status === "blocked" || status === "unavailable_plan") return "../assets/seats/gray.svg";
    return "../assets/seats/green.png";
}

let selectedSeat = null;
const shiftModal = document.getElementById("shiftModal");
const shiftSeatLabel = document.getElementById("shiftSeatLabel");
const morningShiftBtn = document.getElementById("morningShiftBtn");
const eveningShiftBtn = document.getElementById("eveningShiftBtn");
const morningShiftState = document.getElementById("morningShiftState");
const eveningShiftState = document.getElementById("eveningShiftState");

function openSeatPopup() {
    document.getElementById("seatPopup").style.display = "flex";
    loadSeatMap();
}

function closeSeatPopup() {
    document.getElementById("seatPopup").style.display = "none";
}

function closeShiftModal() {
    shiftModal.style.display = "none";
    selectedSeat = null;
}

function openShiftSelector(seat) {
    selectedSeat = seat;
    shiftSeatLabel.textContent = seat.seat_no + " • 6H plan";

    const morningAvailable = seat.morning === "available";
    const eveningAvailable = seat.evening === "available";

    morningShiftBtn.disabled = !morningAvailable;
    eveningShiftBtn.disabled = !eveningAvailable;
    morningShiftState.textContent = morningAvailable ? "Available" : "Already booked";
    eveningShiftState.textContent = eveningAvailable ? "Available" : "Already booked";

    shiftModal.style.display = "flex";
}

morningShiftBtn.onclick = function () {
    if (selectedSeat) bookSeat(selectedSeat.id, "morning");
};

eveningShiftBtn.onclick = function () {
    if (selectedSeat) bookSeat(selectedSeat.id, "evening");
};

function loadSeatMap() {
    fetch("api/get-seats-map.php")
        .then(res => res.json())
        .then(data => {
            const grouped = {};
            seatMapGrid.innerHTML = "";

            data.forEach(seat => {
                const section = seat.section || "General";
                if (!grouped[section]) grouped[section] = [];
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

                    const disabled = ["booked", "blocked", "unavailable_plan", "mine"].includes(seat.status);
                    if (disabled) seatNode.classList.add("disabled");

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
                        seatNode.classList.remove("disabled");
                        seatNode.onclick = function () {
                            if (seat.seat_type === "6h") {
                                openShiftSelector(seat);
                            } else {
                                bookSeat(seat.id, null);
                            }
                        };
                    }

                    grid.appendChild(seatNode);
                });

                section.appendChild(grid);
                seatMapGrid.appendChild(section);
            });
        })
        .catch(() => alert("Unable to load seats right now."));
}

function bookSeat(seatId, shift = null) {
    const body = "seat_id=" + encodeURIComponent(seatId) +
        (shift ? "&shift=" + encodeURIComponent(shift) : "");

    fetch("api/book-seat.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-CSRF-Token": csrfToken
        },
        body: body
    })
    .then(res => res.text())
    .then(response => {
        if (response === "expired") {
            alert("Your subscription has expired.");
            return;
        }
        if (response === "wrong_category") {
            alert("You can book only seats from your subscription category.");
            return;
        }
        if (response === "shift_required") {
            alert("Please select a shift for a 6H seat.");
            return;
        }
        if (response === "already") {
            alert("You already have an active seat booking.");
            closeShiftModal();
            loadSeatMap();
            return;
        }
        if (response === "shift_taken" || response === "taken") {
            alert(response === "shift_taken" ? "That shift was booked by someone else." : "That seat is already booked.");
            closeShiftModal();
            loadSeatMap();
            return;
        }
        if (response === "booked") {
            alert("Seat booked successfully.");
            closeShiftModal();
            loadSeatMap();
            return;
        }
        alert("Unable to book this seat right now.");
    })
    .catch(() => alert("Unable to book this seat right now."));
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

<!-- FINAL 100% ZOOM DESKTOP ALIGNMENT OVERRIDE — corrected -->
<style>
@media (min-width:1201px){
  /* ===== SIDEBAR: no scrollbar, keep every option visible ===== */
  .dashboard{grid-template-columns:220px minmax(0,1fr)!important;}
  .sidebar{
    width:220px!important;
    min-width:220px!important;
    max-width:220px!important;
    height:100vh!important;
    max-height:100vh!important;
    min-height:0!important;
    padding:8px 12px!important;
    gap:6px!important;
    overflow:hidden!important;
    justify-content:flex-start!important;
  }
  .sidebar-top{
    gap:5px!important;
    min-height:0!important;
    height:auto!important;
    flex:0 0 auto!important;
    overflow:visible!important;
  }
  .brand-wrap{
    padding:7px 8px 8px!important;
    border-radius:15px!important;
  }
  .brand-mark{width:72px!important;height:56px!important;margin-bottom:0!important;}
  .brand{font-size:21px!important;line-height:1!important;}
  .brand-sub{font-size:9px!important;margin-top:3px!important;}

  .sidebar-nav{gap:2px!important;}
  .sidebar a,.mode-toggle{
    padding:7px 9px!important;
    min-height:35px!important;
    border-radius:10px!important;
    font-size:12px!important;
    line-height:1.15!important;
  }
  .sidebar a{gap:9px!important;padding-left:10px!important;}
  .nav-icon{width:20px!important;height:20px!important;flex-basis:20px!important;}
  .nav-icon svg{width:19px!important;height:19px!important;}

  .theme-area{
    margin-top:1px!important;
    padding:5px 3px 0!important;
  }
  .theme-title{
    font-size:10px!important;
    margin:0 6px 5px!important;
  }
  .mode-toggle{
    margin:0 0 4px!important;
    min-height:33px!important;
    padding:7px 9px!important;
  }
  .mode-menu{gap:4px!important;margin:0!important;}
  .mode-option{
    min-height:31px!important;
    padding:6px 4px!important;
    border-radius:9px!important;
    font-size:11px!important;
    line-height:1!important;
  }

  .sidebar-footer{
    margin-top:auto!important;
    padding:6px 2px 2px!important;
    flex:0 0 auto!important;
  }
  .sidebar-footer a{
    height:38px!important;
    min-height:38px!important;
    padding:7px 8px!important;
    font-size:12px!important;
    border-radius:10px!important;
  }

  /* ===== MAIN AREA: slightly shorter to reduce page scrolling ===== */
  .main{
    min-width:0!important;
    width:100%!important;
    padding:7px 14px 9px!important;
    gap:8px!important;
    overflow:visible!important;
  }

  /* ===== TOPBAR: exact 122px, timer exact 180x117 and centered ===== */
  .topbar{
    width:100%!important;
    height:122px!important;
    min-height:122px!important;
    max-height:122px!important;
    padding:2px 16px!important;
    gap:12px!important;
    display:grid!important;
    grid-template-columns:minmax(0,1fr) auto!important;
    align-items:center!important;
    overflow:visible!important;
  }
  .welcome-wrap{gap:11px!important;min-width:0!important;align-items:center!important;}
  .profile-avatar-link{
    width:60px!important;height:60px!important;flex:0 0 60px!important;
  }
  .topbar h1{font-size:28px!important;line-height:1.05!important;margin:0!important;}
  .profile-mini-link{font-size:11px!important;margin-top:3px!important;}

  .timer-wrap{
    height:117px!important;
    min-width:0!important;
    display:flex!important;
    align-items:center!important;
    justify-content:flex-end!important;
    gap:9px!important;
    flex-wrap:nowrap!important;
    margin:0!important;
  }
  .timer-box{
    width:180px!important;min-width:180px!important;max-width:180px!important;
    height:117px!important;min-height:117px!important;max-height:117px!important;
    flex:0 0 180px!important;margin:0!important;box-sizing:border-box!important;
  }
  .timer-content{min-width:0!important;}
  .timer-line{gap:7px!important;justify-content:center!important;}
  .timer-clock-icon{width:23px!important;height:23px!important;}
  .timer{font-size:25px!important;min-width:0!important;}
  .timer-sub{font-size:10px!important;margin:6px 0 0 30px!important;}
  .timer-wrap .btn{
    align-self:center!important;
    min-width:100px!important;
    height:48px!important;
    padding:9px 12px!important;
    font-size:12px!important;
  }

  /* ===== STATS: compact but readable ===== */
  .stats{gap:9px!important;}
  .stats>.card{
    height:98px!important;min-height:98px!important;
    padding:10px 12px!important;border-radius:16px!important;
    grid-template-columns:52px minmax(0,1fr)!important;column-gap:8px!important;
  }
  .stat-icon{width:44px!important;height:44px!important;flex-basis:44px!important;}
  .stat-icon svg{width:20px!important;height:20px!important;}
  .stat-copy span{font-size:10px!important;margin-bottom:2px!important;}
  .stat-copy strong{font-size:23px!important;line-height:1!important;}
  .stat-unit{font-size:9px!important;margin-top:2px!important;}

  /* ===== MIDDLE: smaller so next row comes into view ===== */
  .middle{gap:9px!important;}
  .middle>.card{
    height:268px!important;min-height:268px!important;
    padding:13px 15px!important;border-radius:17px!important;
  }
  .middle h3{font-size:19px!important;margin-bottom:5px!important;}

  /* Seat preview: reserve separate rows so nothing overlaps */
  #seatPreview{
    height:195px!important;min-height:195px!important;
    margin-top:3px!important;padding:14px 16px!important;
    display:grid!important;
    grid-template-rows:auto 1fr auto!important;
    gap:4px!important;
    position:relative!important;
    overflow:hidden!important;
    border-radius:16px!important;
  }
  .seat-preview-copy{
    position:relative!important;z-index:2!important;
    width:100%!important;padding-right:0!important;min-width:0!important;
  }
  .seat-preview-copy strong{font-size:20px!important;margin-bottom:3px!important;}
  .seat-preview-copy p{
    font-size:11px!important;line-height:1.35!important;
    max-width:100%!important;margin:0!important;
  }
  .seat-preview-meta{
    position:relative!important;left:auto!important;right:auto!important;bottom:auto!important;
    width:100%!important;height:31px!important;
    display:flex!important;align-items:center!important;justify-content:space-between!important;
    gap:8px!important;margin:0!important;z-index:3!important;
  }
  .miniSeats{padding:3px 6px!important;gap:2px!important;}
  .miniSeats img{width:23px!important;height:23px!important;object-fit:contain!important;}
  .seat-preview-badge{
    padding:7px 11px!important;font-size:10px!important;
    white-space:nowrap!important;flex:0 0 auto!important;
  }
  .seat-legend{
    position:relative!important;left:auto!important;right:auto!important;bottom:auto!important;
    width:100%!important;margin:0!important;padding-top:6px!important;
    display:flex!important;align-items:center!important;gap:8px!important;z-index:3!important;
  }
  .seat-legend span{padding:3px 6px!important;font-size:10px!important;}

  /* Calendar */
  .calendar-card{padding:12px 15px!important;}
  .calendar-head{padding:4px 5px!important;margin-bottom:3px!important;}
  .calendar-title strong{font-size:19px!important;}
  .calendar-title span{font-size:9px!important;margin-top:1px!important;}
  .calendar-chip{padding:5px 8px!important;font-size:8px!important;}
  .calendar-weekdays{gap:3px!important;margin-bottom:2px!important;}
  .calendar-weekdays span{font-size:7px!important;}
  #calendar{gap:3px!important;}
  .day{min-height:25px!important;padding:4px 0!important;border-radius:7px!important;font-size:9px!important;}
  .calendar-empty{min-height:25px!important;}

  /* ===== BOTTOM: smaller ===== */
  .bottom{gap:9px!important;}
  .bottom>.card{
    height:190px!important;min-height:190px!important;
    padding:12px 15px!important;border-radius:17px!important;
  }
  .bottom h3{font-size:19px!important;}
  #weeklyChart{height:118px!important;}
  .diary-box{height:96px!important;}
  .quick-actions{gap:7px!important;}
  .link-btn{padding:8px 10px!important;font-size:11px!important;}
}
</style>

<style id="final-topbar-compact-fix">
/* Final requested change only: topbar 90px, timer 80px */
.topbar{
  height:90px !important;
  min-height:90px !important;
  max-height:90px !important;
  align-items:center !important;
  box-sizing:border-box !important;
}
.timer-wrap{
  height:80px !important;
  min-height:80px !important;
  max-height:80px !important;
  align-items:center !important;
  justify-content:flex-end !important;
  margin:0 !important;
}
.timer-box{
  height:80px !important;
  min-height:80px !important;
  max-height:80px !important;
  margin:0 !important;
  box-sizing:border-box !important;
  display:flex !important;
  align-items:center !important;
  justify-content:center !important;
}
</style>


<style id="final-100-percent-compact-and-seat-link">
/* 100% desktop fit: preserve the current design, only tighten dimensions. */
@media (min-width:1201px){
  .main{
    padding:6px 12px 8px!important;
    gap:7px!important;
  }

  /* Topbar */
  .topbar{
    height:90px!important;
    min-height:90px!important;
    max-height:90px!important;
    padding:2px 14px!important;
    gap:10px!important;
  }
  .profile-avatar-link{
    width:50px!important;
    height:50px!important;
    flex-basis:50px!important;
  }
  .welcome-wrap{gap:9px!important;}
  .topbar h1{font-size:25px!important;}
  .profile-mini-link{font-size:10px!important;}

  /* Timer: exact 80px and vertically centered inside 90px topbar */
  .timer-wrap{
    height:80px!important;
    min-height:80px!important;
    max-height:80px!important;
    align-items:center!important;
    gap:7px!important;
  }
  .timer-box{
    width:180px!important;
    min-width:180px!important;
    max-width:180px!important;
    height:80px!important;
    min-height:80px!important;
    max-height:80px!important;
    flex-basis:180px!important;
  }
  .timer-line{gap:6px!important;}
  .timer-clock-icon{width:21px!important;height:21px!important;}
  .timer{font-size:23px!important;}
  .timer-sub{font-size:9px!important;margin:5px 0 0 27px!important;}
  .timer-wrap .btn{
    min-width:88px!important;
    height:42px!important;
    padding:8px 10px!important;
    font-size:11px!important;
  }

  /* Stats */
  .stats{gap:8px!important;}
  .stats>.card{
    height:88px!important;
    min-height:88px!important;
    padding:8px 10px!important;
    border-radius:15px!important;
    grid-template-columns:46px minmax(0,1fr)!important;
    column-gap:7px!important;
  }
  .stat-icon{width:40px!important;height:40px!important;flex-basis:40px!important;}
  .stat-icon svg{width:18px!important;height:18px!important;}
  .stat-copy span{font-size:9px!important;margin-bottom:2px!important;}
  .stat-copy strong{font-size:21px!important;}
  .stat-unit{font-size:8px!important;}

  /* Middle cards: slightly shorter for 100% viewport */
  .middle{gap:8px!important;}
  .middle>.card{
    height:245px!important;
    min-height:245px!important;
    padding:12px 14px!important;
    border-radius:16px!important;
  }
  .middle h3{font-size:18px!important;margin-bottom:4px!important;}
  #seatPreview{
    height:178px!important;
    min-height:178px!important;
    margin-top:2px!important;
    padding:12px 14px!important;
    gap:3px!important;
    color:inherit!important;
    text-decoration:none!important;
  }
  .seat-preview-copy strong{font-size:19px!important;margin-bottom:2px!important;}
  .seat-preview-copy p{font-size:10px!important;line-height:1.3!important;}
  .seat-preview-meta{height:28px!important;}
  .miniSeats img{width:21px!important;height:21px!important;}
  .seat-preview-badge{padding:6px 9px!important;font-size:9px!important;}
  .seat-legend{padding-top:4px!important;gap:6px!important;}
  .seat-legend span{padding:2px 5px!important;font-size:9px!important;}

  /* Calendar */
  .calendar-card{padding:11px 14px!important;}
  .calendar-head{padding:3px 4px!important;margin-bottom:2px!important;}
  .calendar-title strong{font-size:18px!important;}
  .calendar-title span{font-size:9px!important;}
  .calendar-chip{padding:5px 7px!important;font-size:8px!important;}
  .calendar-weekdays{gap:3px!important;margin-bottom:2px!important;}
  #calendar{gap:3px!important;}
  .day{min-height:23px!important;padding:3px 0!important;font-size:8px!important;border-radius:7px!important;}
  .calendar-empty{min-height:23px!important;}

  /* Bottom row */
  .bottom{gap:8px!important;}
  .bottom>.card{
    height:175px!important;
    min-height:175px!important;
    padding:11px 14px!important;
    border-radius:16px!important;
  }
  .bottom h3{font-size:18px!important;}
  #weeklyChart{height:105px!important;}
  .diary-box{height:88px!important;}
  .quick-actions{gap:6px!important;}
  .link-btn{padding:7px 9px!important;font-size:10px!important;}
}
</style>
