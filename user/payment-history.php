<?php
session_start();
require_once "../config/db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$result = $mysqli->query("
    SELECT * FROM payments 
    WHERE user_id=$user_id 
    ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Payment History</title>
<style>
body{
    font-family:Poppins;
    background:#f4f7fb;
    padding:60px 40px;
}

h2{
    margin-bottom:25px;
}

table{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);
    animation:fadeIn .6s ease;
}

th, td{
    padding:14px;
    text-align:center;
}

th{
    background:#2d6cdf;
    color:white;
}

tr:nth-child(even){
    background:#f9f9f9;
}

.status-approved{ color:green; font-weight:600; }
.status-pending{ color:orange; font-weight:600; }
.status-rejected{ color:red; font-weight:600; }

img{
    width:60px;
    border-radius:6px;
}

@keyframes fadeIn{
    from{opacity:0; transform:translateY(20px);}
    to{opacity:1; transform:translateY(0);}
}
</style>
</head>

<body>

<a href="javascript:history.back()" class="back-btn">← Back</a>

<h2>Payment History</h2>

<table>
<tr>
    <th>Amount</th>
    <th>Transaction ID</th>
    <th>Screenshot</th>
    <th>Status</th>
    <th>Date</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td>₹<?= $row['amount'] ?></td>
    <td><?= htmlspecialchars($row['transaction_id']) ?></td>
    <td>
        <?php if($row['screenshot']): ?>
            <img src="../uploads/<?= $row['screenshot'] ?>">
        <?php endif; ?>
    </td>
    <td class="status-<?= $row['status'] ?>">
        <?= ucfirst($row['status']) ?>
    </td>
    <td><?= $row['created_at'] ?></td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>