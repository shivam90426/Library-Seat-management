<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$totalUsers = $mysqli->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$pendingPayments = $mysqli->query("SELECT COUNT(*) AS total FROM payments WHERE status='pending'")->fetch_assoc()['total'];
$activeSubs = $mysqli->query("SELECT COUNT(*) AS total FROM subscriptions WHERE status='active'")->fetch_assoc()['total'];
$totalSeats = $mysqli->query("SELECT COUNT(*) AS total FROM seats")->fetch_assoc()['total'];
$todayEntries = $mysqli->query("
SELECT COUNT(*) AS total
FROM timings
WHERE DATE(entry_time)=CURDATE()
")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{
--bg:#f3f6fb;
--card:#ffffff;
--text:#162033;
--muted:#667085;
--line:#dbe4f0;
--primary:#2563eb;
--primary-soft:#eff6ff;
--warn:#b45309;
--warn-soft:#fff7ed;
--success:#166534;
--success-soft:#f0fdf4;
}
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
body{background:var(--bg);color:var(--text);}
.dashboard{
min-height:100vh;
display:grid;
grid-template-columns:220px 1fr;
}
.sidebar{
background:#0f172a;
padding:24px 16px;
display:flex;
flex-direction:column;
gap:18px;
}
.brand{
padding:0 10px 10px;
border-bottom:1px solid rgba(255,255,255,0.1);
}
.brand h1{
font-size:20px;
color:#fff;
}
.brand p{
margin-top:4px;
font-size:12px;
color:#94a3b8;
}
.nav{
display:grid;
gap:8px;
}
.nav a{
text-decoration:none;
color:#cbd5e1;
padding:11px 12px;
border-radius:12px;
transition:.2s ease;
}
.nav a:hover,
.nav a.active{
background:rgba(255,255,255,0.08);
color:#fff;
}
.logout{
margin-top:auto;
}
.main{
padding:28px;
display:grid;
gap:18px;
}
.topbar{
display:flex;
justify-content:space-between;
align-items:flex-start;
gap:16px;
flex-wrap:wrap;
}
.topbar h2{
font-size:28px;
}
.topbar p{
margin-top:6px;
color:var(--muted);
}
.status-box{
background:var(--card);
border:1px solid var(--line);
border-radius:16px;
padding:16px 18px;
min-width:220px;
}
.status-box span{
display:block;
font-size:13px;
color:var(--muted);
margin-bottom:6px;
}
.status-box strong{
font-size:26px;
color:var(--primary);
}
.stats{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
gap:14px;
}
.card{
background:var(--card);
border:1px solid var(--line);
border-radius:18px;
padding:18px;
}
.card span{
display:block;
font-size:13px;
color:var(--muted);
margin-bottom:10px;
}
.card strong{
display:block;
font-size:30px;
margin-bottom:12px;
}
.card a{
display:inline-block;
text-decoration:none;
color:var(--primary);
font-size:14px;
font-weight:600;
}
.content{
display:grid;
grid-template-columns:1.1fr .9fr;
gap:18px;
}
.panel{
background:var(--card);
border:1px solid var(--line);
border-radius:18px;
padding:20px;
}
.panel h3{
font-size:18px;
margin-bottom:14px;
}
.quick-links{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(170px,1fr));
gap:12px;
}
.quick-links a{
display:block;
padding:14px 16px;
border-radius:14px;
text-decoration:none;
color:var(--text);
background:#f8fafc;
border:1px solid var(--line);
font-weight:500;
}
.quick-links a small{
display:block;
margin-top:4px;
color:var(--muted);
font-weight:400;
}
.info-list{
display:grid;
gap:12px;
}
.info-item{
display:flex;
justify-content:space-between;
align-items:center;
gap:12px;
padding:14px 16px;
border-radius:14px;
background:#f8fafc;
border:1px solid var(--line);
}
.info-item strong{
font-size:20px;
}
.info-item.warning{
background:var(--warn-soft);
}
.info-item.warning strong{
color:var(--warn);
}
.info-item.success{
background:var(--success-soft);
}
.info-item.success strong{
color:var(--success);
}
@media(max-width:920px){
.dashboard{
grid-template-columns:1fr;
}
.content{
grid-template-columns:1fr;
}
.sidebar{
padding-bottom:12px;
}
.logout{
margin-top:0;
}
}
@media(max-width:640px){
.main{
padding:18px;
}
.topbar h2{
font-size:24px;
}
}
</style>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <div class="brand">
            <h1>Library Admin</h1>
            <p>Simple control panel</p>
        </div>

        <nav class="nav">
            <a class="active" href="dashboard.php">Dashboard</a>
            <a href="payments.php">Payments</a>
            <a href="users.php">Users</a>
            <a href="subscriptions.php">Subscriptions</a>
            <a href="layout-builder.php">Seat Builder</a>
            <a href="section-builder.php">Section Builder</a>
        </nav>

        <div class="nav logout">
            <a href="../logout.php">Logout</a>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div>
                <h2>Admin Dashboard</h2>
                <p>Jo kaam roz chahiye, woh yahin se directly open ho jayega.</p>
            </div>
            <div class="status-box">
                <span>Pending payments</span>
                <strong><?= $pendingPayments ?></strong>
            </div>
        </div>

        <section class="stats">
            <div class="card">
                <span>Total Users</span>
                <strong><?= $totalUsers ?></strong>
                <a href="users.php">Open users</a>
            </div>
            <div class="card">
                <span>Active Subscriptions</span>
                <strong><?= $activeSubs ?></strong>
                <a href="subscriptions.php">Open subscriptions</a>
            </div>
            <div class="card">
                <span>Total Seats</span>
                <strong><?= $totalSeats ?></strong>
                <a href="layout-builder.php">Open seat builder</a>
            </div>
            <div class="card">
                <span>Today's Entries</span>
                <strong><?= $todayEntries ?></strong>
                <a href="subscriptions.php">View records</a>
            </div>
        </section>

        <section class="content">
            <div class="panel">
                <h3>Quick Actions</h3>
                <div class="quick-links">
                    <a href="payments.php">Review Payments<small>Approve or reject pending entries</small></a>
                    <a href="users.php">Manage Users<small>Search users and account status</small></a>
                    <a href="subscriptions.php">Check Plans<small>See active and expired subscriptions</small></a>
                    <a href="layout-builder.php">Seat Layout<small>Update seats and arrangement</small></a>
                    <a href="section-builder.php">Sections<small>Open section setup page</small></a>
                </div>
            </div>

            <div class="panel">
                <h3>Today</h3>
                <div class="info-list">
                    <div class="info-item warning">
                        <span>Pending approvals</span>
                        <strong><?= $pendingPayments ?></strong>
                    </div>
                    <div class="info-item success">
                        <span>Active plans</span>
                        <strong><?= $activeSubs ?></strong>
                    </div>
                    <div class="info-item">
                        <span>Seat count</span>
                        <strong><?= $totalSeats ?></strong>
                    </div>
                    <div class="info-item">
                        <span>Entries today</span>
                        <strong><?= $todayEntries ?></strong>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>
