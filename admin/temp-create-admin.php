<?php
require_once "../config/db.php";

/* 🔐 Simple Security Key */
$secret_key = "createadmin123";   // change this if you want

if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    die("Unauthorized Access");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name     = trim($_POST["name"]);
    $email    = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($name) || empty($email) || empty($password)) {
        $message = "All fields are required!";
    } else {

        // Check duplicate
        $check = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();

        if ($exists) {
            $message = "Email already exists!";
        } else {

            $hash_pass = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $mysqli->prepare("
                INSERT INTO users (name, email, password, role)
                VALUES (?, ?, ?, 'admin')
            ");
            $stmt->bind_param("sss", $name, $email, $hash_pass);
            $stmt->execute();

            $message = "Admin created successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Create Admin</title>
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
h2{text-align:center;margin-bottom:20px;}
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
.msg{
    background:#dcfce7;
    padding:10px;
    border-radius:10px;
    margin-bottom:15px;
    text-align:center;
}
</style>
</head>
<body>

<div class="box">
<h2>Create Admin</h2>

<?php if($message): ?>
<div class="msg"><?php echo $message; ?></div>
<?php endif; ?>

<form method="POST">
    <input type="text" name="name" placeholder="Admin Name" required>
    <input type="email" name="email" placeholder="Admin Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Create Admin</button>
</form>

</div>

</body>
</html>