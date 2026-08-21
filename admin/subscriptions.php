<?php
session_start();
require "../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$status = $_GET['status'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$planOptions = [
    '6h_monthly' => ['label'=>'6 Hour Plan','plan_name'=>'1 Month Plan','seat_type'=>'6h','price'=>450.00,'duration_months'=>1,'bonus_days'=>0,'renewal_type'=>'normal'],
    '12h_monthly' => ['label'=>'12 Hour Plan','plan_name'=>'1 Month Plan','seat_type'=>'12h','price'=>800.00,'duration_months'=>1,'bonus_days'=>0,'renewal_type'=>'normal'],
    '24h_monthly' => ['label'=>'24 Hour Plan','plan_name'=>'1 Month Plan','seat_type'=>'24h','price'=>1000.00,'duration_months'=>1,'bonus_days'=>0,'renewal_type'=>'normal'],
    'premium_3m' => ['label'=>'3 Month Premium (6 Hour)','plan_name'=>'3 Month Premium','seat_type'=>'6h','price'=>2500.00,'duration_months'=>3,'bonus_days'=>7,'renewal_type'=>'bulk_3month']
];
$message = ""; $messageType = "success";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_plan') {
    $userId = intval($_POST['user_id'] ?? 0);
    $subId = intval($_POST['subscription_id'] ?? 0);
    $planKey = $_POST['plan_key'] ?? '';
    if ($userId <= 0 || $subId <= 0 || !isset($planOptions[$planKey])) {
        $message = "Invalid plan change request."; $messageType = "error";
    } else {
        $plan = $planOptions[$planKey];
        $startDate = date("Y-m-d");
        $endDate = date("Y-m-d", strtotime($startDate . " +" . $plan['duration_months'] . " month +" . $plan['bonus_days'] . " day"));
        $stmt = $mysqli->prepare("UPDATE subscriptions SET plan_name=?, seat_type=?, price=?, duration_months=?, bonus_days=?, renewal_type=?, start_date=?, end_date=?, status='active' WHERE id=? AND user_id=?");
        $stmt->bind_param("ssdiiissii",$plan['plan_name'],$plan['seat_type'],$plan['price'],$plan['duration_months'],$plan['bonus_days'],$plan['renewal_type'],$startDate,$endDate,$subId,$userId);
        $stmt->execute();
        $message = "Plan updated successfully."; 
    }
}

$where = [];
if (in_array($status, ['active', 'expired', 'cancelled', 'queued'], true)) {
    $where[] = "s.status='" . $mysqli->real_escape_string($status) . "'";
}
if ($search !== '') {
    $safeSearch = "%" . $mysqli->real_escape_string($search) . "%";
    $where[] = "(u.name LIKE '$safeSearch' OR s.plan_name LIKE '$safeSearch' OR s.seat_type LIKE '$safeSearch')";
}

$summary = $mysqli->query("
SELECT
    SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) AS active_count,
    SUM(CASE WHEN status='expired' THEN 1 ELSE 0 END) AS expired_count,
    SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled_count,
    SUM(CASE WHEN status='queued' THEN 1 ELSE 0 END) AS queued_count
FROM subscriptions
")->fetch_assoc();

$query = "
SELECT s.*, u.name, u.email
FROM subscriptions s
JOIN users u ON u.id = s.user_id
";
if ($where) {
    $query .= " WHERE " . implode(" AND ", $where);
}
$query .= " ORDER BY s.created_at DESC, s.id DESC";
$subs = $mysqli->query($query);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Subscriptions</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
body{background:linear-gradient(135deg,#0f172a,#1e293b);color:#fff;min-height:100vh;padding:32px 18px;}
.page{max-width:1320px;margin:0 auto;}
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
.filters{display:grid;grid-template-columns:1fr 180px auto;gap:12px;align-items:end;margin-bottom:18px;}
.field label{display:block;font-size:13px;color:#cbd5e1;margin-bottom:6px;}
.field input,.field select{width:100%;padding:12px 14px;border-radius:14px;border:1px solid rgba(255,255,255,0.08);background:rgba(15,23,42,0.6);color:#fff;outline:none;}
.submit-btn{border:none;border-radius:14px;padding:12px 16px;color:#fff;cursor:pointer;font-weight:600;background:linear-gradient(90deg,#2563eb,#4f46e5);}
.table-wrap{overflow:auto;border-radius:18px;}
table{width:100%;border-collapse:collapse;min-width:1120px;}
th,td{padding:14px 12px;text-align:left;vertical-align:top;}
th{color:#cbd5e1;font-size:13px;border-bottom:1px solid rgba(255,255,255,0.08);}
td{border-bottom:1px solid rgba(255,255,255,0.06);}
.chip{display:inline-flex;padding:6px 10px;border-radius:999px;font-size:12px;text-transform:capitalize;}
.chip.active{background:rgba(22,163,74,0.18);color:#86efac;}
.chip.expired{background:rgba(245,158,11,0.18);color:#fcd34d;}
.chip.cancelled{background:rgba(220,38,38,0.18);color:#fca5a5;}
.chip.queued{background:rgba(59,130,246,0.18);color:#93c5fd;}
.muted{color:#cbd5e1;font-size:13px;}
@media(max-width:900px){.filters{grid-template-columns:1fr;}}
.message{padding:13px 16px;border-radius:15px;margin-bottom:16px}.message.success{background:rgba(34,197,94,.12);border:1px solid rgba(74,222,128,.25);color:#86efac}.message.error{background:rgba(239,68,68,.12);border:1px solid rgba(248,113,113,.25);color:#fca5a5}.manage-plan-form{display:grid;gap:6px;min-width:180px}.manage-plan-form select{padding:8px 9px;border-radius:10px;border:1px solid rgba(165,205,245,.18);background:rgba(3,15,28,.62);color:#fff}.manage-plan-form button{border:0;border-radius:10px;padding:8px 10px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;cursor:pointer;font-weight:600}
</style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <a href="dashboard.php" class="back-link">Back to Dashboard</a>
        <a href="payments.php" class="action-link">Open Payments</a>
    </div>

    <div class="hero">
        <h1>Subscriptions</h1>
        <p>Review active, expired, and cancelled plans with quick filters so admins can answer user plan questions faster.</p>
    </div>

    <div class="stats">
        <div class="stat-card"><span>Active</span><strong><?= intval($summary['active_count'] ?? 0) ?></strong></div>
        <div class="stat-card"><span>Expired</span><strong><?= intval($summary['expired_count'] ?? 0) ?></strong></div>
        <div class="stat-card"><span>Cancelled</span><strong><?= intval($summary['cancelled_count'] ?? 0) ?></strong></div>
        <div class="stat-card"><span>Queued</span><strong><?= intval($summary['queued_count'] ?? 0) ?></strong></div>
    </div>

    <div class="panel">
        <?php if ($message !== ""): ?><div class="message <?= $messageType === "error" ? "error" : "success" ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <form method="GET" class="filters">
            <div class="field">
                <label>Search by user, plan, or seat type</label>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search subscriptions">
            </div>
            <div class="field">
                <label>Status</label>
                <select name="status">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>Expired</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    <option value="queued" <?= $status === 'queued' ? 'selected' : '' ?>>Queued</option>
                </select>
            </div>
            <button class="submit-btn" type="submit">Apply Filters</button>
        </form>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>User</th>
                    <th>Plan</th>
                    <th>Seat Type</th>
                    <th>Price</th>
                    <th>Duration</th>
                    <th>Bonus Days</th>
                    <th>Status</th>
                    <th>Start</th>
                    <th>End</th><th>Manage Plan</th>
                </tr>
                <?php while ($s = $subs->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div><?= htmlspecialchars($s['name']) ?></div>
                            <div class="muted"><?= htmlspecialchars($s['email']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($s['plan_name']) ?></td>
                        <td><?= htmlspecialchars($s['seat_type']) ?></td>
                        <td>Rs <?= number_format((float)$s['price'], 2) ?></td>
                        <td><?= intval($s['duration_months']) ?> month(s)</td>
                        <td><?= intval($s['bonus_days']) ?></td>
                        <td><span class="chip <?= htmlspecialchars($s['status']) ?>"><?= htmlspecialchars($s['status']) ?></span></td>
                        <td><?= htmlspecialchars($s['start_date']) ?></td>
                        <td><?= htmlspecialchars($s['end_date']) ?></td>
                    <td>
                            <?php if ($s['status'] === 'active'): ?>
                            <form method="POST" class="manage-plan-form">
                                <input type="hidden" name="action" value="change_plan">
                                <input type="hidden" name="user_id" value="<?= intval($s['user_id']) ?>">
                                <input type="hidden" name="subscription_id" value="<?= intval($s['id']) ?>">
                                <select name="plan_key">
                                    <?php foreach ($planOptions as $key => $plan): ?>
                                        <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($plan['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit">Change</button>
                            </form>
                            <?php else: ?><span class="muted">—</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>
