<?php
session_start();
require_once "../config/db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$result = $mysqli->query("
    SELECT * FROM subscriptions 
    WHERE user_id=$user_id 
    ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Subscription History</title>
<style>
body{
    font-family:Poppins;
    background:#f4f7fb;
    padding:60px 40px;
}

h2{
    margin-bottom:25px;
}

.card{
    background:white;
    padding:20px;
    border-radius:14px;
    margin-bottom:20px;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);
    animation:fadeIn .6s ease;
    transition:.3s;
}

.card:hover{
    transform:translateY(-5px);
}

.plan{
    font-size:18px;
    font-weight:600;
    color:#2d6cdf;
}

.status-active{ color:green; font-weight:600; }
.status-expired{ color:red; font-weight:600; }

@keyframes fadeIn{
    from{opacity:0; transform:translateY(20px);}
    to{opacity:1; transform:translateY(0);}
}
</style>
</head>

<body>

<a href="javascript:history.back()" class="back-btn">← Back</a>

<h2>Subscription History</h2>

<?php while($row = $result->fetch_assoc()): ?>
<div class="card">
    <div class="plan"><?= $row['plan_name'] ?></div>
    <p>Price: ₹<?= $row['price'] ?></p>
    <p>Duration: <?= $row['duration_months'] ?> Month(s)</p>
    <p>Start: <?= $row['start_date'] ?></p>
    <p>End: <?= $row['end_date'] ?></p>
    <p class="status-<?= $row['status'] ?>">
        <?= ucfirst($row['status']) ?>
    </p>
</div>
<?php endwhile; ?>

</body>
</html>