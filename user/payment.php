<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get active payment settings
$result = $mysqli->query("SELECT upi_id, qr_image FROM payment_settings WHERE active = 1 LIMIT 1");
$payment = $result->fetch_assoc();

if (!$payment) {
    die("Payment settings not configured. Contact Admin.");
}

$upi_id = htmlspecialchars($payment['upi_id']);
$qr_image = "../" . $payment['qr_image'];

$message = "";

// Handle payment submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $transaction_id = trim($_POST['transaction_id']);
    $amount = floatval($_POST['amount']);

    // Screenshot upload
    $upload_dir = "../uploads/payments/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $screenshot_name = time() . "_" . $_FILES['screenshot']['name'];
    $target_path = $upload_dir . $screenshot_name;

    if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $target_path)) {

        $stmt = $mysqli->prepare("INSERT INTO payments (user_id, transaction_id, amount, payment_method, status, created_at) VALUES (?, ?, ?, 'UPI', 'pending', NOW())");

        $stmt->bind_param("isd", $user_id, $transaction_id, $amount);
        $stmt->execute();

        $message = "Payment submitted successfully! Waiting for admin approval.";

    } else {
        $message = "Screenshot upload failed!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Complete Payment</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
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
    margin:0;
    font-family:'Poppins',sans-serif;
    background:linear-gradient(135deg,#e0f2fe,#f8fafc);
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.card{
    width:520px;
    background:white;
    padding:30px;
    border-radius:25px;
    box-shadow:0 25px 50px rgba(0,0,0,0.1);
    animation:fadeIn .6s ease;
}

@keyframes fadeIn{
    from{opacity:0;transform:translateY(20px);}
    to{opacity:1;transform:translateY(0);}
}

h2{text-align:center;}

.qr-box{text-align:center;margin-bottom:20px;}

.qr-box img{
    width:200px;
    border-radius:15px;
    box-shadow:0 10px 20px rgba(0,0,0,0.15);
}

.upi-box{
    background:#f1f5f9;
    padding:12px;
    border-radius:12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

input{
    width:100%;
    padding:12px;
    margin-bottom:15px;
    border-radius:10px;
    border:1px solid #ddd;
}

button{
    width:100%;
    padding:12px;
    border:none;
    border-radius:10px;
    background:linear-gradient(90deg,#2563eb,#7c3aed);
    color:white;
    font-weight:600;
    cursor:pointer;
    transition:.3s;
}

button:hover{
    transform:translateY(-2px);
}

.copy-btn{
    width:auto;
    padding:6px 12px;
    background:#2563eb;
}

.success{
    background:#dcfce7;
    padding:10px;
    border-radius:10px;
    margin-bottom:15px;
    text-align:center;
}

</style>
</head>
<body>
<a href="javascript:history.back()" class="back-btn">
    ← Back
</a>
<div class="card">

<h2>Scan & Pay</h2>

<?php if($message): ?>
<div class="success"><?php echo $message; ?></div>
<?php endif; ?>

<div class="qr-box">
    <img src="<?php echo $qr_image; ?>" id="qrImage"><br><br>
    <button type="button" class="copy-btn" onclick="downloadQR()">Download QR</button>
</div>

<div class="upi-box">
    <span id="upiText"><?php echo $upi_id; ?></span>
    <button type="button" class="copy-btn" onclick="copyUPI()">Copy</button>
</div>

<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="amount" value="999">

<input type="text" name="transaction_id" placeholder="Enter Transaction ID (UTR)" required>

<input type="file" name="screenshot" accept="image/*" required>

<button type="submit">Submit Payment</button>

</form>

</div>

<script>
function copyUPI() {
    const upi = document.getElementById("upiText").innerText;
    navigator.clipboard.writeText(upi);
    alert("UPI ID Copied!");
}

function downloadQR(){
    const img = document.getElementById("qrImage").src;
    const link = document.createElement('a');
    link.href = img;
    link.download = "Library-QR.png";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

</body>
</html>