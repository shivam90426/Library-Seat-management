<?php
session_start();
require_once "../config/db.php";

/* Admin Authentication */
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../login.php");
    exit;
}

/* APPROVE PAYMENT */
if(isset($_GET['approve'])){

    $id = intval($_GET['approve']);

    // Get payment details
    $result = $mysqli->query("SELECT * FROM payments WHERE id=$id AND status='pending'");
    
    if($result && $result->num_rows > 0){

        $payment = $result->fetch_assoc();
        $user_id = $payment['user_id'];
        $amount  = $payment['amount'];

        /* Decide Plan Based On Amount */
        if($amount == 999){
            $months = 1;
        } elseif($amount == 2499){
            $months = 3;
        } else {
            $months = 1; // fallback safety
        }

        $start = date("Y-m-d");
        $end   = date("Y-m-d", strtotime("+$months month"));

        /* Update Payment Status */
        $mysqli->query("UPDATE payments SET status='approved' WHERE id=$id");

        /* Insert Subscription */
        $stmt = $mysqli->prepare("
            INSERT INTO subscriptions 
            (user_id, plan_name, price, duration_months, start_date, end_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");

        $plan_name = $months . " Month Plan";

        $stmt->bind_param("isisss", 
            $user_id, 
            $plan_name, 
            $amount, 
            $months, 
            $start, 
            $end
        );

        $stmt->execute();

        header("Location: payments.php?success=1");
        exit;
    }
}

/* REJECT PAYMENT */
if(isset($_GET['reject'])){

    $id = intval($_GET['reject']);
    $mysqli->query("UPDATE payments SET status='rejected' WHERE id=$id");

    header("Location: payments.php?rejected=1");
    exit;
}

/* Fetch Pending Payments */
$payments = $mysqli->query("
    SELECT p.*, u.name 
    FROM payments p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>

<title>Manage Payments</title>
<style>
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

.back-btn:hover{
    transform:translateY(-3px);
    box-shadow:0 10px 20px rgba(0,0,0,0.25);
}
body{
    font-family: Poppins;
    background:#f4f7fb;
    padding:40px;
}

h2{
    margin-bottom:20px;
}

table{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);
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

.btn{
    padding:6px 14px;
    border-radius:6px;
    text-decoration:none;
    color:white;
    font-size:14px;
}

.approve{
    background:#28a745;
}

.reject{
    background:#dc3545;
}

img{
    width:60px;
    border-radius:6px;
}
</style>
</head>

<body>
<a href="javascript:history.back()" class="back-btn">
    ← Back
</a>
<h2>Pending Payments</h2>

<?php if(isset($_GET['success'])): ?>
<p style="color:green;">Payment Approved Successfully</p>
<?php endif; ?>

<?php if(isset($_GET['rejected'])): ?>
<p style="color:red;">Payment Rejected</p>
<?php endif; ?>

<table>
<tr>
    <th>User</th>
    <th>Amount</th>
    <th>Transaction ID</th>
    <th>Screenshot</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php while($row = $payments->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td>₹<?= $row['amount'] ?></td>
    <td><?= htmlspecialchars($row['transaction_id']) ?></td>
    <td>
        <?php if($row['screenshot']): ?>
            <img src="../uploads/<?= $row['screenshot'] ?>">
        <?php endif; ?>
    </td>
    <td><?= $row['status'] ?></td>
    <td>
        <?php if($row['status'] == 'pending'): ?>
            <a class="btn approve" href="?approve=<?= $row['id'] ?>">Approve</a>
            <a class="btn reject" href="?reject=<?= $row['id'] ?>">Reject</a>
        <?php else: ?>
            —
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>