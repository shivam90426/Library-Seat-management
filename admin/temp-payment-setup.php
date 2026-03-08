<?php
session_start();
require_once "../config/db.php";

// Optional simple protection
$secret_key = "setup123"; // change if you want

if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    die("Unauthorized Access");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $upi_id = trim($_POST["upi_id"]);
    $qr_path = null;

    // Create upload folder if not exists
    $uploadDir = "../uploads/qr/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Upload QR
    if (!empty($_FILES["qr_image"]["name"])) {

        $fileName = time() . "_" . basename($_FILES["qr_image"]["name"]);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES["qr_image"]["tmp_name"], $targetPath)) {
            $qr_path = "uploads/qr/" . $fileName;
        }
    }

    // Deactivate old
    $mysqli->query("UPDATE payment_settings SET active = 0");

    // Insert new
    $stmt = $mysqli->prepare("
        INSERT INTO payment_settings (upi_id, qr_image, active, updated_by)
        VALUES (?, ?, 1, 1)
    ");
    $stmt->bind_param("ss", $upi_id, $qr_path);
    $stmt->execute();

    $message = "Payment settings updated successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Temporary Payment Setup</title>
<style>
body{
    font-family:Poppins;
    background:linear-gradient(135deg,#e0f2fe,#f1f5f9);
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}
.box{
    width:400px;
    padding:30px;
    background:white;
    border-radius:20px;
    box-shadow:0 20px 40px rgba(0,0,0,0.1);
}
h2{
    text-align:center;
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
    background:#2563eb;
    color:white;
    border:none;
    border-radius:10px;
    cursor:pointer;
}
.success{
    background:#dcfce7;
    padding:10px;
    border-radius:10px;
    margin-bottom:15px;
    color:#065f46;
}
</style>
</head>
<body>

<div class="box">
<h2>Temporary Payment Setup</h2>

<?php if($message): ?>
<div class="success"><?php echo $message; ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <input type="text" name="upi_id" placeholder="Enter UPI ID" required>
    <input type="file" name="qr_image" accept="image/*" required>
    <button type="submit">Update Payment</button>
</form>

</div>

</body>
</html>