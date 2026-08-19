<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$search = trim($_GET['q'] ?? '');
$where = "";
if ($search !== '') {
    $safeSearch = "%" . $mysqli->real_escape_string($search) . "%";
    $where = "WHERE u.name LIKE '$safeSearch' OR u.email LIKE '$safeSearch' OR u.role LIKE '$safeSearch'";
}

$stats = $mysqli->query("
SELECT
    COUNT(*) AS total_users,
    SUM(CASE WHEN role='admin' THEN 1 ELSE 0 END) AS total_admins,
    SUM(CASE WHEN role='user' THEN 1 ELSE 0 END) AS total_members,
    SUM(CASE WHEN is_active=1 THEN 1 ELSE 0 END) AS active_accounts
FROM users
")->fetch_assoc();

$users = $mysqli->query("
SELECT
    u.id,
    u.name,
    u.email,
    u.role,
    u.phone,
    u.is_active,
    u.last_login,
    u.created_at,
    (
        SELECT COUNT(*)
        FROM subscriptions s
        WHERE s.user_id = u.id AND s.status='active' AND s.end_date >= CURDATE()
    ) AS active_subscription_count
FROM users u
$where
ORDER BY u.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
body{background:linear-gradient(135deg,#0f172a,#1e293b);color:#fff;min-height:100vh;padding:32px 18px;}
.page{max-width:1280px;margin:0 auto;}
.topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:22px;}
.back-link,.action-link{display:inline-flex;padding:10px 18px;border-radius:999px;text-decoration:none;color:#fff;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.08);}
.hero,.panel,.stat-card{background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);border-radius:24px;box-shadow:0 20px 40px rgba(0,0,0,0.18);}
.hero{padding:22px 24px;margin-bottom:18px;}
.hero p{color:#cbd5e1;line-height:1.6;margin-top:8px;}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:18px;}
.stat-card{padding:18px 20px;}
.stat-card span{display:block;color:#cbd5e1;font-size:14px;margin-bottom:8px;}
.stat-card strong{font-size:28px;}
.panel{padding:20px;}
.filters{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:end;margin-bottom:18px;}
.field label{display:block;font-size:13px;color:#cbd5e1;margin-bottom:6px;}
.field input{width:100%;padding:12px 14px;border-radius:14px;border:1px solid rgba(255,255,255,0.08);background:rgba(15,23,42,0.6);color:#fff;outline:none;}
.submit-btn{border:none;border-radius:14px;padding:12px 16px;color:#fff;cursor:pointer;font-weight:600;background:linear-gradient(90deg,#2563eb,#4f46e5);}
.table-wrap{overflow:auto;border-radius:18px;}
table{width:100%;border-collapse:collapse;min-width:980px;}
th,td{padding:14px 12px;text-align:left;vertical-align:top;}
th{color:#cbd5e1;font-size:13px;border-bottom:1px solid rgba(255,255,255,0.08);}
td{border-bottom:1px solid rgba(255,255,255,0.06);}
.chip{display:inline-flex;padding:6px 10px;border-radius:999px;font-size:12px;}
.chip.user{background:rgba(37,99,235,0.18);color:#93c5fd;}
.chip.admin{background:rgba(139,92,246,0.18);color:#c4b5fd;}
.chip.active{background:rgba(22,163,74,0.18);color:#86efac;}
.chip.inactive{background:rgba(220,38,38,0.18);color:#fca5a5;}
.muted{color:#cbd5e1;font-size:13px;}
@media(max-width:900px){.filters{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <a href="dashboard.php" class="back-link">Back to Dashboard</a>
        <a href="subscriptions.php" class="action-link">Open Subscriptions</a>
    </div>

    <div class="hero">
        <h1>Users</h1>
        <p>Find members faster, see who has an active subscription, and review account health without switching across multiple rough admin screens.</p>
    </div>

    <div class="stats">
        <div class="stat-card"><span>Total Accounts</span><strong><?= intval($stats['total_users'] ?? 0) ?></strong></div>
        <div class="stat-card"><span>Total Users</span><strong><?= intval($stats['total_members'] ?? 0) ?></strong></div>
        <div class="stat-card"><span>Admins</span><strong><?= intval($stats['total_admins'] ?? 0) ?></strong></div>
        <div class="stat-card"><span>Active Accounts</span><strong><?= intval($stats['active_accounts'] ?? 0) ?></strong></div>
    </div>

    <div class="panel">
        <form method="GET" class="filters">
            <div class="field">
                <label>Search by name, email, or role</label>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search users">
            </div>
            <button class="submit-btn" type="submit">Search</button>
        </form>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Account</th>
                    <th>Phone</th>
                    <th>Active Plan</th>
                    <th>Created</th>
                    <th>Last Login</th>
                </tr>
                <?php while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="chip <?= htmlspecialchars($u['role']) ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                        <td><span class="chip <?= intval($u['is_active']) === 1 ? 'active' : 'inactive' ?>"><?= intval($u['is_active']) === 1 ? 'Active' : 'Inactive' ?></span></td>
                        <td><?= htmlspecialchars($u['phone'] ?: '-') ?></td>
                        <td><?= intval($u['active_subscription_count']) > 0 ? 'Yes' : 'No' ?></td>
                        <td><?= htmlspecialchars($u['created_at']) ?></td>
                        <td><span class="muted"><?= htmlspecialchars($u['last_login'] ?: 'Never') ?></span></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>
