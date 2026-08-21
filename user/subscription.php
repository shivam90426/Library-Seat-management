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