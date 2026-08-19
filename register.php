<?php
require_once "includes/security.php";
library_system_bootstrap();
require_once "config/db.php";

$success="";
$error="";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_csrf_token();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || strlen($name) > 100) {
        $error = "Please enter a valid name";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } else {
        // Check duplicate email
        $check = $mysqli->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $check->bind_param("s",$email);
        $check->execute();
        $check->store_result();

        if($check->num_rows>0){
            $error="Email already registered";
        } else {

            $hashed = password_hash($password,PASSWORD_DEFAULT);
            $role="user";
            $profile_pic = NULL;

            // Image upload
            if(!empty($_FILES['profile_pic']['name']) && is_uploaded_file($_FILES['profile_pic']['tmp_name'])){
                $target_dir="assets/images/profile/";
                if(!is_dir($target_dir)){
                    mkdir($target_dir,0755,true);
                }

                if ($_FILES['profile_pic']['size'] > 2 * 1024 * 1024) {
                    $error = "Profile picture must be under 2 MB";
                } else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($_FILES['profile_pic']['tmp_name']);
                    $allowedTypes = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png'
                    ];

                    if (!isset($allowedTypes[$mimeType])) {
                        $error = "Only JPG and PNG images are allowed";
                    } else {
                        $file_name = bin2hex(random_bytes(16)) . "." . $allowedTypes[$mimeType];
                        $target_file = $target_dir . $file_name;
                        if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                            $error = "Unable to upload profile picture";
                        } else {
                            $profile_pic = $target_file;
                        }
                    }
                }
            }

            if ($error === "") {
                $stmt=$mysqli->prepare("INSERT INTO users (name,email,password,role,profile_pic,created_at) VALUES (?,?,?,?,?,NOW())");
                $stmt->bind_param("sssss",$name,$email,$hashed,$role,$profile_pic);

                if($stmt->execute()){
                    $success="Registration Successful! Redirecting...";
                } else {
                    $error="Something went wrong!";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Register - Library</title>

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
height:680px;
display:flex;
border-radius:30px;
overflow:hidden;
box-shadow:0 40px 80px rgba(0,0,0,0.15);
background:white;
}

/* LEFT IMAGE */
.left{
width:50%;
padding:30px;
display:flex;
align-items:center;
justify-content:center;
background:#f8fafc;
}

.left img{
width:100%;
height:100%;
object-fit:cover;
border-radius:30px;
box-shadow:0 20px 50px rgba(0,0,0,0.2);
transition:0.8s;
}

.left img:hover{
transform:scale(1.05);
}

/* RIGHT FORM */
.right{
width:50%;
display:flex;
justify-content:center;
align-items:center;
}

.card{
width:420px;
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
margin-bottom:20px;
color:#1e3a8a;
}

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

/* Password wrapper */
.password-wrapper{
position:relative;
}

.password-wrapper span{
position:absolute;
right:15px;
top:50%;
transform:translateY(-50%);
cursor:pointer;
font-size:14px;
color:#2563eb;
font-weight:600;
}

/* Upload preview */
.preview{
width:90px;
height:90px;
border-radius:50%;
margin:auto;
margin-bottom:15px;
background:#e2e8f0;
overflow:hidden;
display:flex;
align-items:center;
justify-content:center;
}

.preview img{
width:100%;
height:100%;
object-fit:cover;
}

button{
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

button:hover{
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

.success{
background:#dcfce7;
color:#166534;
padding:10px;
border-radius:10px;
margin-bottom:15px;
text-align:center;
}

.login-link{
margin-top:15px;
text-align:center;
font-size:14px;
}

.login-link a{
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

<h2>Create Account</h2>

<?php if($error!=""){ ?>
<div class="error"><?= $error ?></div>
<?php } ?>

<?php if($success!=""){ ?>
<div class="success"><?= $success ?></div>
<script>
setTimeout(()=>{ window.location="login.php"; },2000);
</script>
<?php } ?>

<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

<div class="preview" id="imagePreview">
    <span style="color:#64748b;">Preview</span>
</div>

<input type="file" name="profile_pic" accept="image/*" onchange="previewImage(event)">

<input type="text" name="name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email Address" required>

<div class="password-wrapper">
<input type="password" name="password" id="password" placeholder="Create Password" required>
<span onclick="togglePassword()">Show</span>
</div>

<button>Register</button>

</form>

<div class="login-link">
Already have account? <a href="login.php">Login Here</a>
</div>

</div>
</div>
</div>

<script>
// Preview image
function previewImage(event){
    const reader=new FileReader();
    reader.onload=function(){
        const output=document.getElementById('imagePreview');
        output.innerHTML="<img src='"+reader.result+"'>";
    }
    reader.readAsDataURL(event.target.files[0]);
}

// Show Hide Password
function togglePassword(){
    const pass=document.getElementById("password");
    if(pass.type==="password"){
        pass.type="text";
    } else {
        pass.type="password";
    }
}
</script>

</body>
</html>
