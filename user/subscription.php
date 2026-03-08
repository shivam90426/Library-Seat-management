<?php
session_start();
require_once "../config/db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== "user"){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $mysqli->prepare("
    SELECT id FROM subscriptions 
    WHERE user_id=? 
    AND status='active' 
    AND end_date >= CURDATE()
    LIMIT 1
");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$result = $stmt->get_result();
$hasActive = $result->num_rows > 0;
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Select Plan</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
.back-btn{
    position:fixed;
    top:20px;
    left:20px;
    background:linear-gradient(135deg,#2d6cdf,#4e8df5);
    color:white;
    padding:8px 16px;
    border-radius:30px;
    text-decoration:none;
    font-size:14px;
    box-shadow:0 5px 15px rgba(0,0,0,0.15);
    transition:all .3s ease;
}
body{
background:linear-gradient(120deg,#eef2ff,#e0f2fe);
padding:40px;
}

h1{
text-align:center;
margin-bottom:40px;
color:#1e3a8a;
}

.grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
gap:30px;
max-width:1100px;
margin:auto;
}

.card{
background:white;
padding:30px;
border-radius:20px;
box-shadow:0 20px 40px rgba(0,0,0,0.1);
text-align:center;
transition:0.3s;
}

.card:hover{
transform:translateY(-5px);
}

.price{
font-size:26px;
margin:15px 0;
color:#2563eb;
font-weight:600;
}

.features{
margin:15px 0;
color:#64748b;
line-height:1.6;
}

.button{
display:inline-block;
margin-top:15px;
padding:10px 20px;
background:linear-gradient(90deg,#2563eb,#4f46e5);
color:white;
border-radius:10px;
text-decoration:none;
transition:0.3s;
}

.button:hover{
transform:translateY(-2px);
box-shadow:0 10px 20px rgba(37,99,235,0.4);
}

.warning{
background:#fee2e2;
color:#991b1b;
padding:15px;
border-radius:10px;
max-width:500px;
margin:0 auto 30px;
text-align:center;
}
</style>
</head>

<body>
<a href="javascript:history.back()" class="back-btn">
    ← Back
</a>
<h1>Select Your Subscription Plan</h1>

<?php if($hasActive): ?>
<div class="warning">
You already have an active subscription.
Please wait until expiry.
</div>
<?php endif; ?>

<div class="grid">

<?php if(!$hasActive): ?>

<div class="card">
    <h2>6 Hour Plan</h2>
    <div class="price">₹450 / Month</div>
    <div class="features">
        ✔ 30 Days Access<br>
        ✔ 6 Hours Daily<br>
        ✔ Seat Booking Enabled
    </div>
    <a href="payment.php?plan=6" class="button">Choose Plan</a>
</div>

<div class="card">
    <h2>12 Hour Plan</h2>
    <div class="price">₹800 / Month</div>
    <div class="features">
        ✔ 30 Days Access<br>
        ✔ 12 Hours Daily<br>
        ✔ Priority Booking
    </div>
    <a href="payment.php?plan=12" class="button">Choose Plan</a>
</div>

<div class="card">
    <h2>24 Hour Plan</h2>
    <div class="price">₹1000 / Month</div>
    <div class="features">
        ✔ 30 Days Access<br>
        ✔ Full Day Access<br>
        ✔ Fixed Seat
    </div>
    <a href="payment.php?plan=24" class="button">Choose Plan</a>
</div>

<div class="card">
    <h2>3 Month Premium</h2>
    <div class="price">₹2500 / 3 Months</div>
    <div class="features">
        ✔ 90 Days Access<br>
        ✔ +7 Days Bonus<br>
        ✔ Best Value Plan
    </div>
    <a href="payment.php?plan=3m" class="button">Choose Plan</a>
</div>

<?php endif; ?>

</div>

</body>
</html>