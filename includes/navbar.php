<?php if(!isset($_SESSION)) session_start(); ?>

<style>
.navbar{
background:rgba(255,255,255,.85);
backdrop-filter:blur(12px);
padding:12px 25px;
display:flex;
justify-content:space-between;
align-items:center;
box-shadow:0 10px 25px rgba(0,0,0,.1);
}

.navbar a{
text-decoration:none;
color:#1e293b;
margin-right:15px;
font-weight:600;
transition:.2s;
}

.navbar a:hover{
color:#2563eb;
}
</style>

<div class="navbar">
<div>
<b>Library System</b>
</div>

<div>
<?php if(isset($_SESSION['role']) && $_SESSION['role']=="user"){ ?>
<a href="/library_system/user/dashboard.php">Dashboard</a>
<a href="/library_system/user/book-seat.php">Book Seat</a>
<a href="/library_system/user/my-seat.php">My Seat</a>
<a href="/library_system/user/payment-history.php">Payments</a>
<a href="/library_system/user/subscription-history.php">Subscriptions</a>
<a href="/library_system/user/usage-analytics.php">Usage</a>
<?php } ?>

<a href="/library_system/logout.php">Logout</a>
</div>
</div>