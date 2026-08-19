<?php
require_once "includes/security.php";
library_system_bootstrap();
require_once "config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_csrf_token();

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'user');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '' || !in_array($role, ['user', 'admin'], true)) {
        $error = "Invalid Email or Password";
    } else {
        $stmt = $mysqli->prepare("SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1");
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows === 1){
            $user = $result->fetch_assoc();

            if(password_verify($password, $user['password'])){
                set_logged_in_user($user);

                if($user['role'] === "admin"){
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: user/dashboard.php");
                }
                exit;
            } else {
                $error = "Invalid Email or Password";
            }
        } else {
            $error = "Invalid Email or Password";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Library Login</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}

body{
height:100vh;
display:flex;
justify-content:center;
align-items:center;
background:linear-gradient(120deg,#e0f2fe,#eef2ff);
}

.container{
width:1100px;
height:650px;
display:flex;
border-radius:30px;
overflow:hidden;
box-shadow:0 40px 80px rgba(0,0,0,0.15);
background:white;
}

/* IMAGE SIDE */
.left{
width:50%;
padding:30px;
background:#f8fafc;
display:flex;
align-items:center;
justify-content:center;
}

.left img{
width:100%;
height:100%;
object-fit:cover;
border-radius:30px;
box-shadow:0 20px 50px rgba(0,0,0,0.2);
transition:0.8s ease;
}

.left img:hover{
transform:scale(1.05);
}

/* LOGIN SIDE */
.right{
width:50%;
display:flex;
justify-content:center;
align-items:center;
}

.card{
width:380px;
padding:40px;
border-radius:25px;
background:rgba(255,255,255,0.6);
backdrop-filter:blur(20px);
box-shadow:0 20px 40px rgba(0,0,0,0.1);
animation:fadeIn 0.8s ease;
}

@keyframes fadeIn{
from{opacity:0;transform:translateY(20px);}
to{opacity:1;transform:translateY(0);}
}

h2{
text-align:center;
margin-bottom:25px;
color:#1e3a8a;
}

/* SLIDING TOGGLE */
.toggle-wrapper{
position:relative;
background:#e2e8f0;
border-radius:50px;
display:flex;
margin-bottom:25px;
overflow:hidden;
}

.toggle-wrapper button{
flex:1;
padding:10px;
border:none;
background:none;
cursor:pointer;
font-weight:600;
z-index:2;
transition:0.3s;
}

.slider{
position:absolute;
top:0;
left:0;
width:50%;
height:100%;
background:linear-gradient(90deg,#2563eb,#1e40af);
border-radius:50px;
transition:0.4s ease;
}

.toggle-wrapper.admin .slider{
left:50%;
}

.toggle-wrapper button.active{
color:white;
}

/* INPUT */
input{
width:100%;
padding:14px;
margin-bottom:15px;
border-radius:12px;
border:1px solid #cbd5e1;
outline:none;
transition:0.3s;
}

input:focus{
border-color:#2563eb;
box-shadow:0 0 12px rgba(37,99,235,0.3);
transform:scale(1.02);
}

/* BUTTON */
.login-btn{
width:100%;
padding:14px;
border:none;
border-radius:12px;
background:linear-gradient(90deg,#2563eb,#4f46e5);
color:white;
font-weight:600;
cursor:pointer;
transition:0.3s;
}

.login-btn:hover{
transform:translateY(-3px);
box-shadow:0 10px 20px rgba(37,99,235,0.4);
}

.error{
background:#fee2e2;
color:#991b1b;
padding:10px;
border-radius:10px;
margin-bottom:15px;
text-align:center;
}

.register{
margin-top:15px;
text-align:center;
font-size:14px;
}

.register a{
color:#2563eb;
font-weight:600;
text-decoration:none;
}
</style>
</head>

<body>

<div class="container">

<div class="left">
    <img src="assets/images/library-bg.jpg">
</div>

<div class="right">

<div class="card">

<h2>Library System Login</h2>

<?php if($error!=""){ ?>
<div class="error"><?= $error ?></div>
<?php } ?>

<div class="toggle-wrapper" id="toggle">
    <div class="slider"></div>
    <button type="button" class="active" onclick="setRole('user')">User</button>
    <button type="button" onclick="setRole('admin')">Admin</button>
</div>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="role" id="role" value="user">
    <input type="email" name="email" placeholder="Enter Email" required>
    <input type="password" name="password" placeholder="Enter Password" required>
    <button class="login-btn">Login</button>
</form>

<div class="register">
New User? <a href="register.php">Register Here</a>
</div>

</div>
</div>
</div>

<script>
function setRole(role){
    document.getElementById("role").value = role;
    const toggle = document.getElementById("toggle");
    const buttons = toggle.querySelectorAll("button");

    buttons.forEach(btn=>btn.classList.remove("active"));

    if(role === "admin"){
        toggle.classList.add("admin");
        buttons[1].classList.add("active");
    } else {
        toggle.classList.remove("admin");
        buttons[0].classList.add("active");
    }
}
</script>

</body>
</html>
