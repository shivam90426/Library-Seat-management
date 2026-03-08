<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

/* ===== Dashboard Stats ===== */

$totalUsers = $mysqli->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];

$pendingPayments = $mysqli->query("SELECT COUNT(*) as total FROM payments WHERE status='pending'")->fetch_assoc()['total'];

$activeSubs = $mysqli->query("SELECT COUNT(*) as total FROM subscriptions WHERE status='active'")->fetch_assoc()['total'];

$totalSeats = $mysqli->query("SELECT COUNT(*) as total FROM seats")->fetch_assoc()['total'];

$todayEntries = $mysqli->query("
SELECT COUNT(*) as total 
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

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Poppins;
}

body{
display:flex;
background:linear-gradient(135deg,#eef2ff,#e0e7ff);
min-height:100vh;
}

/* ===== Sidebar ===== */

.sidebar{
width:260px;
background:linear-gradient(180deg,#1e3a8a,#2563eb);
color:white;
padding:30px 20px;
display:flex;
flex-direction:column;
justify-content:space-between;
box-shadow:5px 0 25px rgba(0,0,0,0.1);
}

.sidebar h2{
margin-bottom:40px;
}

.nav-links a{
display:block;
color:white;
text-decoration:none;
padding:12px 15px;
border-radius:10px;
margin-bottom:10px;
transition:.3s;
}

.nav-links a:hover{
background:rgba(255,255,255,0.2);
transform:translateX(6px);
}

.logout{
background:#dc2626;
text-align:center;
margin-top:20px;
}

/* ===== Main ===== */

.main{
flex:1;
padding:40px;
}

.header{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:30px;
}

.header h1{
font-size:28px;
color:#1e293b;
}

.cards{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
gap:25px;
margin-bottom:35px;
}

/* ===== Cards ===== */

.card{
background:white;
padding:25px;
border-radius:18px;
box-shadow:0 15px 30px rgba(0,0,0,0.08);
transition:.35s;
position:relative;
overflow:hidden;
}

.card:hover{
transform:translateY(-6px);
box-shadow:0 18px 40px rgba(0,0,0,0.12);
}

.card h3{
font-size:16px;
color:#64748b;
margin-bottom:8px;
}

.card p{
font-size:32px;
font-weight:600;
color:#2563eb;
}

.manage-btn{
display:inline-block;
margin-top:10px;
padding:8px 14px;
background:linear-gradient(135deg,#2563eb,#4f46e5);
color:white;
border-radius:8px;
font-size:13px;
text-decoration:none;
}

/* ===== Management Section ===== */

.management{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
gap:20px;
}

.manage-card{
background:white;
padding:20px;
border-radius:16px;
box-shadow:0 12px 28px rgba(0,0,0,0.07);
transition:.3s;
}

.manage-card:hover{
transform:translateY(-5px);
}

.manage-card h4{
margin-bottom:10px;
}

.manage-card a{
display:inline-block;
margin-top:8px;
padding:8px 14px;
background:#2563eb;
color:white;
border-radius:8px;
font-size:13px;
text-decoration:none;
}

@media(max-width:768px){
.sidebar{
display:none;
}
}

</style>
</head>

<body>

<div class="sidebar">

<div>

<h2>Admin Panel</h2>

<div class="nav-links">

<a href="dashboard.php">Dashboard</a>

<a href="payments.php">Payments</a>

<a href="users.php">Users</a>

<a href="subscriptions.php">Subscriptions</a>

<a href="layout-builder.php">Seat Builder</a>

<a href="section-builder.php">Section Builder</a>

</div>

</div>

<a href="../logout.php" class="nav-links logout">Logout</a>

</div>


<div class="main">

<div class="header">
<h1>Admin Dashboard</h1>
</div>


<div class="cards">

<div class="card">
<h3>Total Users</h3>
<p><?= $totalUsers ?></p>
</div>

<div class="card">
<h3>Pending Payments</h3>
<p><?= $pendingPayments ?></p>
<a href="payments.php" class="manage-btn">Review</a>
</div>

<div class="card">
<h3>Active Subscriptions</h3>
<p><?= $activeSubs ?></p>
</div>

<div class="card">
<h3>Total Seats</h3>
<p><?= $totalSeats ?></p>
</div>

<div class="card">
<h3>Today's Entries</h3>
<p><?= $todayEntries ?></p>
</div>

</div>


<div class="management">

<div class="manage-card">
<h4>Seat Layout Builder</h4>
<p>Create or modify seat layout</p>
<a href="layout-builder.php">Open</a>
</div>

<div class="manage-card">
<h4>Section Builder</h4>
<p>Manage seat sections</p>
<a href="section-builder.php">Open</a>
</div>

<div class="manage-card">
<h4>Payments</h4>
<p>Approve or reject payments</p>
<a href="payments.php">Manage</a>
</div>

<div class="manage-card">
<h4>User Management</h4>
<p>View and control users</p>
<a href="users.php">Manage</a>
</div>

</div>

</div>

</body>
</html>