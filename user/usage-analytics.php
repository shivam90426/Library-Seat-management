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
    new Chart(document.getElementById(canvasId), {
        type: "bar",
        data: {
            labels: labels,
            datasets: [{
                label: chartLabel,
                data: hours,
                backgroundColor: color,
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
                    ticks: {
                        color: "#cbd5e1"
                    },
                    grid: {
                        color: "rgba(255,255,255,0.08)"
                    }
                },
                x: {
                    ticks: {
                        color: "#cbd5e1"
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
