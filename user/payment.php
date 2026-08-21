<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$planKey = $_GET['plan'] ?? $_POST['plan'] ?? '6';
$planMap = [
    '6'  => ['name'=>'1 Month Plan','seat_type'=>'6h','amount'=>450.00,'label'=>'6 Hour Plan'],
    '12' => ['name'=>'1 Month Plan','seat_type'=>'12h','amount'=>800.00,'label'=>'12 Hour Plan'],
    '24' => ['name'=>'1 Month Plan','seat_type'=>'24h','amount'=>1000.00,'label'=>'24 Hour Plan'],
    '3m' => ['name'=>'3 Month Premium','seat_type'=>'6h','amount'=>2500.00,'label'=>'3 Month Premium']
];
if (!isset($planMap[$planKey])) {
    $planKey = '6';
}
$selectedPlan = $planMap[$planKey];

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
    $amount = (float)($selectedPlan['amount']);

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

.selected-plan{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;margin-bottom:15px;border:1px solid rgba(165,205,245,.20);border-radius:12px;background:rgba(255,255,255,.05);color:#f7fbff}.selected-plan span{color:#a78bfa;font-weight:600}</style>
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

<input type="hidden" name="plan" value="<?= htmlspecialchars($planKey) ?>">
<input type="hidden" name="amount" value="<?= htmlspecialchars($selectedPlan['amount']) ?>">
<div class="selected-plan"><strong><?= htmlspecialchars($selectedPlan['label']) ?></strong><span>₹<?= number_format($selectedPlan['amount'],2) ?></span></div>

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