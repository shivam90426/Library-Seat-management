<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    header("Location: ../login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);

$summaryStmt = $mysqli->prepare("
SELECT
    ROUND(IFNULL(SUM(duration_minutes), 0) / 60, 2) AS total_hours,
    ROUND(IFNULL(SUM(CASE WHEN MONTH(entry_time)=MONTH(CURDATE()) AND YEAR(entry_time)=YEAR(CURDATE()) THEN duration_minutes ELSE 0 END), 0) / 60, 2) AS monthly_hours,
    COUNT(DISTINCT DATE(entry_time)) AS active_days
FROM timings
WHERE user_id=?
");
$summaryStmt->bind_param("i", $user_id);
$summaryStmt->execute();
$summary = $summaryStmt->get_result()->fetch_assoc();

$planStmt = $mysqli->prepare("
SELECT seat_type, start_date, end_date
FROM subscriptions
WHERE user_id=? AND status='active'
ORDER BY id DESC
LIMIT 1
");
$planStmt->bind_param("i", $user_id);
$planStmt->execute();
$plan = $planStmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Usage Analytics</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
body{
min-height:100vh;
background:linear-gradient(135deg,#0f172a,#111827);
color:#fff;
padding:32px 18px;
}
.page{
max-width:1180px;
margin:0 auto;
}
.back-link{
display:inline-flex;
margin-bottom:22px;
padding:10px 18px;
border-radius:999px;
background:rgba(255,255,255,0.08);
color:#fff;
text-decoration:none;
border:1px solid rgba(255,255,255,0.12);
}
.header{
display:flex;
justify-content:space-between;
gap:18px;
align-items:flex-start;
margin-bottom:24px;
flex-wrap:wrap;
}
.stats{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
gap:18px;
margin-bottom:24px;
}
.card{
background:rgba(255,255,255,0.06);
border:1px solid rgba(255,255,255,0.1);
border-radius:24px;
padding:22px;
box-shadow:0 20px 40px rgba(0,0,0,0.18);
}
.card span{
display:block;
font-size:14px;
color:#cbd5e1;
margin-bottom:8px;
}
.card strong{
font-size:30px;
}
.charts{
display:grid;
grid-template-columns:1fr 1fr;
gap:20px;
}
.chart-card{
background:rgba(255,255,255,0.06);
border:1px solid rgba(255,255,255,0.1);
border-radius:24px;
padding:22px;
min-height:360px;
}
.chart-card h3{
margin-bottom:18px;
}
canvas{
width:100% !important;
height:280px !important;
}
@media(max-width: 920px){
.charts{
grid-template-columns:1fr;
}
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

    <div class="header">
        <div>
            <h1>Usage Analytics</h1>
        </div>
        <div class="card" style="min-width:260px;">
            <span>Active Plan</span>
            <strong><?= htmlspecialchars($plan['seat_type'] ?? 'No active plan') ?></strong>
        </div>
    </div>

    <div class="stats">
        <div class="card">
            <span>Total Recorded Hours</span>
            <strong><?= htmlspecialchars($summary['total_hours'] ?? 0) ?></strong>
        </div>
        <div class="card">
            <span>Hours This Month</span>
            <strong><?= htmlspecialchars($summary['monthly_hours'] ?? 0) ?></strong>
        </div>
        <div class="card">
            <span>Active Study Days</span>
            <strong><?= htmlspecialchars($summary['active_days'] ?? 0) ?></strong>
        </div>
    </div>

    <div class="charts">
        <div class="chart-card">
            <h3>Daily Usage This Month</h3>
            <canvas id="dailyUsageChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>Monthly Usage This Year</h3>
            <canvas id="monthlyUsageChart"></canvas>
        </div>
    </div>
</div>

<script>
function renderChart(canvasId, chartLabel, labels, hours, color) {
    const maxValue = Math.max(1, ...hours.map(Number));
    const scaleMax = Math.ceil(maxValue);

    new Chart(document.getElementById(canvasId), {
        type: "bar",
        data: {
            labels: labels,
            datasets: [{
                label: chartLabel,
                data: hours,
                backgroundColor: color,
                borderRadius: 8,
                maxBarThickness: 26
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return " " + Number(context.raw || 0).toFixed(2) + " hours";
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    min: 0,
                    max: scaleMax,
                    ticks: {
                        color: "#cbd5e1",
                        stepSize: 1,
                        precision: 0,
                        callback: value => value + "h"
                    },
                    grid: {
                        color: "rgba(255,255,255,0.08)"
                    }
                },
                x: {
                    ticks: {
                        color: "#cbd5e1",
                        autoSkip: false,
                        maxRotation: 0
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

fetch("api/get-daily-usage.php")
    .then(res => res.json())
    .then(data => {
        renderChart("dailyUsageChart", "Hours", data.labels, data.hours, "rgba(59,130,246,0.8)");
    });

fetch("api/get-monthly-usage.php")
    .then(res => res.json())
    .then(data => {
        renderChart("monthlyUsageChart", "Hours", data.labels, data.hours, "rgba(16,185,129,0.8)");
    });
</script>
</body>
</html>
