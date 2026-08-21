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
--bg:#06111f;--bg2:#0a1b2d;--card:rgba(13,31,50,.68);--border:rgba(165,205,245,.20);
--text:#f7fbff;--muted:#aebfd2;--blue:#3b82f6;--purple:#8b5cf6;--green:#22c55e;--orange:#f59e0b;
}
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif}
body{background:radial-gradient(circle at 10% 0%,rgba(56,189,248,.14),transparent 30%),radial-gradient(circle at 90% 0%,rgba(139,92,246,.15),transparent 30%),linear-gradient(135deg,var(--bg),var(--bg2));color:var(--text);min-height:100vh}
.dashboard{min-height:100vh;display:grid;grid-template-columns:235px 1fr}
.sidebar{padding:20px 14px;display:flex;flex-direction:column;gap:16px;background:rgba(4,15,28,.78);border-right:1px solid var(--border);backdrop-filter:blur(20px);box-shadow:20px 0 50px rgba(0,0,0,.16)}
.brand{padding:10px 12px 18px;border-bottom:1px solid rgba(255,255,255,.09)}
.brand h1{font-size:21px;color:#fff}.brand p{margin-top:4px;font-size:12px;color:#8fa3ba}
.nav{display:grid;gap:7px}.nav a{text-decoration:none;color:#b8c9db;padding:11px 12px;border-radius:12px;transition:.2s;border:1px solid transparent}.nav a:hover,.nav a.active{background:rgba(255,255,255,.07);border-color:rgba(165,205,245,.12);color:#fff}
.logout{margin-top:auto}.logout a{background:rgba(239,68,68,.08);color:#fca5a5}.logout a:hover{background:rgba(239,68,68,.15)}
.main{padding:24px;display:grid;gap:16px;max-width:1500px;width:100%}
.topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}.topbar h2{font-size:28px}.topbar p{margin-top:5px;color:var(--muted);font-size:13px}
.status-box,.card,.panel{background:linear-gradient(135deg,rgba(15,36,58,.76),rgba(7,22,38,.64));border:1px solid var(--border);border-radius:20px;box-shadow:0 22px 55px rgba(0,0,0,.22),inset 0 1px rgba(255,255,255,.05);backdrop-filter:blur(18px)}
.status-box{padding:14px 18px;min-width:210px}.status-box span{display:block;font-size:12px;color:var(--muted);margin-bottom:5px}.status-box strong{font-size:25px;color:#a78bfa}
.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px}.card{padding:17px}.card span{display:block;font-size:12px;color:var(--muted);margin-bottom:8px}.card strong{display:block;font-size:29px;margin-bottom:9px}.card a{color:#8db7ff;text-decoration:none;font-size:13px;font-weight:600}
.content{display:grid;grid-template-columns:1.1fr .9fr;gap:16px}.panel{padding:18px}.panel h3{font-size:17px;margin-bottom:13px}.quick-links{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.quick-links a{display:block;padding:13px 14px;border-radius:14px;text-decoration:none;color:var(--text);background:rgba(255,255,255,.045);border:1px solid rgba(165,205,245,.13);transition:.2s}.quick-links a:hover{background:rgba(255,255,255,.08);border-color:rgba(139,92,246,.34);transform:translateY(-1px)}.quick-links a small{display:block;margin-top:4px;color:var(--muted);font-weight:400}
.info-list{display:grid;gap:9px}.info-item{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 14px;border-radius:13px;background:rgba(255,255,255,.045);border:1px solid rgba(165,205,245,.12)}.info-item strong{font-size:19px}.info-item.warning strong{color:#fbbf24}.info-item.success strong{color:#4ade80}
@media(max-width:1000px){.stats{grid-template-columns:repeat(2,1fr)}.content{grid-template-columns:1fr}}@media(max-width:760px){.dashboard{grid-template-columns:1fr}.sidebar{min-height:auto}.logout{margin-top:0}.stats{grid-template-columns:1fr}.quick-links{grid-template-columns:1fr}.main{padding:17px}}
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
