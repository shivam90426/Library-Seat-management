<?php
require_once "../includes/security.php";
library_system_bootstrap();
require_once "../config/db.php";

require_login('user');

$user_id = intval($_SESSION['user_id']);
$success = '';
$error = '';

$stmt = $mysqli->prepare("SELECT id, name, email, phone, profile_pic FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    exit('User not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '' || strlen($name) > 100) {
        $error = 'Please enter a valid name.';
    } elseif ($phone !== '' && (strlen($phone) > 20 || !preg_match('/^[0-9+()\-\s]+$/', $phone))) {
        $error = 'Please enter a valid phone number.';
    }

    $profile_pic = $user['profile_pic'];

    if ($error === '' && !empty($_FILES['profile_pic']['name']) && is_uploaded_file($_FILES['profile_pic']['tmp_name'])) {
        if ($_FILES['profile_pic']['size'] > 2 * 1024 * 1024) {
            $error = 'Profile picture must be under 2 MB.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($_FILES['profile_pic']['tmp_name']);
            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png'
            ];

            if (!isset($allowedTypes[$mimeType])) {
                $error = 'Only JPG and PNG images are allowed.';
            } else {
                $targetDir = '../assets/images/profile/';
                if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
                    $error = 'Unable to create profile image folder.';
                } else {
                    $fileName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
                    $targetFile = $targetDir . $fileName;

                    if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
                        $error = 'Unable to upload profile picture.';
                    } else {
                        $profile_pic = 'assets/images/profile/' . $fileName;
                    }
                }
            }
        }
    }

    if ($error === '') {
        $phoneDb = $phone === '' ? null : $phone;
        $update = $mysqli->prepare("UPDATE users SET name=?, phone=?, profile_pic=? WHERE id=?");
        $update->bind_param("sssi", $name, $phoneDb, $profile_pic, $user_id);

        if ($update->execute()) {
            $_SESSION['name'] = $name;
            $user['name'] = $name;
            $user['phone'] = $phoneDb;
            $user['profile_pic'] = $profile_pic;
            $success = 'Profile updated successfully.';
        } else {
            $error = 'Unable to update your profile right now.';
        }
    }
}

$userInitial = strtoupper(substr(trim($user['name']), 0, 1));
$profilePic = trim((string)($user['profile_pic'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile - Library</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
--bg:#0f172a;
--gradient:linear-gradient(135deg,#0f172a,#1e293b);
--text:#f8fafc;
--muted:#cbd5e1;
--panel:rgba(255,255,255,.07);
--panel-strong:rgba(255,255,255,.10);
--border:rgba(255,255,255,.12);
--input:rgba(15,23,42,.55);
--accent:#3b82f6;
}
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
body{min-height:100vh;background:var(--gradient);color:var(--text);padding:28px 18px;}
.page{max-width:900px;margin:0 auto;}
.topbar{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:22px;}
.back,.logout{padding:10px 16px;border-radius:999px;text-decoration:none;color:var(--text);background:var(--panel);border:1px solid var(--border);transition:.2s ease;}
.back:hover,.logout:hover{background:var(--panel-strong);transform:translateY(-1px);}
.card{background:var(--panel);border:1px solid var(--border);border-radius:28px;padding:30px;box-shadow:0 24px 60px rgba(0,0,0,.22);backdrop-filter:blur(18px);}
.header{display:flex;align-items:center;gap:20px;padding-bottom:26px;margin-bottom:26px;border-bottom:1px solid var(--border);}
.avatar{width:104px;height:104px;border-radius:30px;overflow:hidden;flex:0 0 104px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#2563eb,#7c3aed);box-shadow:0 15px 35px rgba(37,99,235,.25);}
.avatar img{width:100%;height:100%;object-fit:cover;}
.avatar span{font-size:38px;font-weight:700;}
.header h1{font-size:28px;margin-bottom:4px;}
.header p{color:var(--muted);font-size:14px;}
.message{padding:12px 15px;border-radius:14px;margin-bottom:18px;}
.success{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);color:#86efac;}
.error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#fca5a5;}
.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;}
.field{display:flex;flex-direction:column;gap:8px;}
.field.full{grid-column:1/-1;}
label{font-size:13px;color:var(--muted);font-weight:500;}
input{width:100%;padding:13px 14px;border-radius:14px;border:1px solid var(--border);outline:none;background:var(--input);color:var(--text);font-size:14px;}
input:focus{border-color:rgba(59,130,246,.65);box-shadow:0 0 0 3px rgba(59,130,246,.12);}
input[readonly]{opacity:.7;cursor:not-allowed;}
.help{font-size:12px;color:var(--muted);}
.file-input{padding:10px;}
.actions{display:flex;justify-content:flex-end;gap:12px;margin-top:24px;}
.btn{border:none;border-radius:14px;padding:12px 20px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;}
.btn-secondary{background:var(--panel-strong);color:var(--text);border:1px solid var(--border);}
.btn-primary{background:linear-gradient(135deg,#2563eb,#4f46e5);color:#fff;box-shadow:0 10px 25px rgba(37,99,235,.25);}
.note{margin-top:20px;padding:15px 16px;border-radius:16px;background:rgba(15,23,42,.38);color:var(--muted);font-size:13px;line-height:1.6;}
@media(max-width:650px){body{padding:18px 12px}.card{padding:22px;border-radius:22px}.header{align-items:flex-start}.avatar{width:82px;height:82px;flex-basis:82px;border-radius:24px}.avatar span{font-size:30px}.header h1{font-size:23px}.form-grid{grid-template-columns:1fr}.field.full{grid-column:auto}.actions{flex-direction:column}.btn{width:100%}.topbar{align-items:stretch;}.back,.logout{text-align:center;flex:1;}}

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
<div class="page">
    <div class="topbar">
        <a class="back" href="dashboard.php">← Back to Dashboard</a>
        <a class="logout" href="../logout.php">Logout</a>
    </div>

    <div class="card">
        <div class="header">
            <div class="avatar">
                <?php if ($profilePic !== ''): ?>
                    <img src="../<?= htmlspecialchars($profilePic) ?>" alt="Profile photo">
                <?php else: ?>
                    <span><?= htmlspecialchars($userInitial) ?></span>
                <?php endif; ?>
            </div>
            <div>
                <h1>My Profile</h1>
                <p>Update your personal information and profile photo.</p>
            </div>
        </div>

        <?php if ($success !== ''): ?>
            <div class="message success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-grid">
                <div class="field">
                    <label for="name">Full Name</label>
                    <input id="name" type="text" name="name" maxlength="100" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>

                <div class="field">
                    <label for="phone">Phone Number</label>
                    <input id="phone" type="text" name="phone" maxlength="20" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Enter phone number">
                </div>

                <div class="field full">
                    <label for="email">Email Address</label>
                    <input id="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                    <span class="help">Email address cannot be changed from the profile page.</span>
                </div>

                <div class="field full">
                    <label for="profile_pic">Profile Photo</label>
                    <input class="file-input" id="profile_pic" type="file" name="profile_pic" accept="image/jpeg,image/png">
                    <span class="help">JPG or PNG only, maximum 2 MB.</span>
                </div>
            </div>

            <div class="actions">
                <a class="btn btn-secondary" href="dashboard.php">Cancel</a>
                <button class="btn btn-primary" type="submit">Save Changes</button>
            </div>
        </form>

        <div class="note">
            Your email remains locked so your login identity stays unchanged. Name, phone number and profile photo can be updated anytime.
        </div>
    </div>
</div>
</body>
</html>
