<?php
session_start();
require_once "../config/db.php";
require_once "includes/plan_helpers.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";
$messageType = "success";
$planOptions = get_subscription_plan_options();
$selectedStatus = $_GET['status'] ?? 'all';
$search = trim($_GET['q'] ?? '');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';
    $paymentId = intval($_POST['payment_id'] ?? 0);

    if ($paymentId > 0 && $action === 'approve') {
        $planKey = $_POST['plan_key'] ?? get_default_plan_key_for_amount(floatval($_POST['amount'] ?? 0));

        if (!isset($planOptions[$planKey])) {
            $message = "Invalid plan selected.";
            $messageType = "error";
        } else {
            $paymentStmt = $mysqli->prepare("
                SELECT p.id, p.user_id, p.amount, COALESCE(NULLIF(p.status, ''), 'pending') AS status
                FROM payments p
                WHERE p.id=? AND (p.status='pending' OR p.status='' OR p.status IS NULL)
                LIMIT 1
            ");
            $paymentStmt->bind_param("i", $paymentId);
            $paymentStmt->execute();
            $payment = $paymentStmt->get_result()->fetch_assoc();

            if (!$payment) {
                $message = "Payment is no longer pending.";
                $messageType = "error";
            } else {
                $activeSubStmt = $mysqli->prepare("
                    SELECT id
                    FROM subscriptions
                    WHERE user_id=? AND status='active' AND end_date >= CURDATE()
                    LIMIT 1
                ");
                $activeSubStmt->bind_param("i", $payment['user_id']);
                $activeSubStmt->execute();

                if ($activeSubStmt->get_result()->num_rows > 0) {
                    $message = "This user already has an active subscription. Manage that first, then approve again if needed.";
                    $messageType = "error";
                } else {
                    $plan = $planOptions[$planKey];
                    $startDate = date("Y-m-d");
                    $endDate = date("Y-m-d", strtotime($startDate . " +" . intval($plan['duration_months']) . " month +" . intval($plan['bonus_days']) . " day"));
                    $adminId = intval($_SESSION['user_id'] ?? 0);

                    $mysqli->begin_transaction();

                    try {
                        $updateStmt = $mysqli->prepare("
                            UPDATE payments
                            SET status='success', verified_by_admin=?, verified_at=NOW()
                            WHERE id=? AND (status='pending' OR status='' OR status IS NULL)
                        ");
                        $updateStmt->bind_param("ii", $adminId, $paymentId);
                        $updateStmt->execute();

                        $subscriptionStmt = $mysqli->prepare("
                            INSERT INTO subscriptions
                            (user_id, plan_name, seat_type, price, duration_months, bonus_days, renewal_type, start_date, end_date, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
                        ");
                        $subscriptionStmt->bind_param(
                            "issdiisss",
                            $payment['user_id'],
                            $plan['plan_name'],
                            $plan['seat_type'],
                            $plan['price'],
                            $plan['duration_months'],
                            $plan['bonus_days'],
                            $plan['renewal_type'],
                            $startDate,
                            $endDate
                        );
                        $subscriptionStmt->execute();

                        $mysqli->commit();
                        $message = "Payment approved and subscription created.";
                    } catch (Throwable $e) {
                        $mysqli->rollback();
                        $message = "Approval failed. Please try again.";
                        $messageType = "error";
                    }
                }
            }
        }
    } elseif ($paymentId > 0 && $action === 'reject') {
        $adminId = intval($_SESSION['user_id'] ?? 0);
        $rejectStmt = $mysqli->prepare("
            UPDATE payments
            SET status='rejected', verified_by_admin=?, verified_at=NOW()
            WHERE id=? AND (status='pending' OR status='' OR status IS NULL)
        ");
        $rejectStmt->bind_param("ii", $adminId, $paymentId);
        $rejectStmt->execute();
        $message = $rejectStmt->affected_rows > 0 ? "Payment rejected." : "Payment is no longer pending.";
        $messageType = $rejectStmt->affected_rows > 0 ? "success" : "error";
    }
}

$summary = $mysqli->query("
    SELECT
        SUM(CASE WHEN status='pending' OR status='' OR status IS NULL THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status='success' THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) AS rejected_count
    FROM payments
")->fetch_assoc();

$where = [];
if ($selectedStatus === 'pending') {
    $where[] = "(p.status='pending' OR p.status='' OR p.status IS NULL)";
} elseif (in_array($selectedStatus, ['success', 'rejected'], true)) {
    $where[] = "p.status='" . $mysqli->real_escape_string($selectedStatus) . "'";
}

if ($search !== '') {
    $safeSearch = "%" . $mysqli->real_escape_string($search) . "%";
    $where[] = "(u.name LIKE '$safeSearch' OR u.email LIKE '$safeSearch' OR COALESCE(p.transaction_id, '') LIKE '$safeSearch' OR COALESCE(p.utr_no, '') LIKE '$safeSearch')";
}

$query = "
SELECT
    p.id,
    p.user_id,
    p.amount,
    p.transaction_id,
    p.utr_no,
    p.screenshot_path,
    COALESCE(NULLIF(p.status, ''), 'pending') AS status,
    p.created_at,
    u.name,
    u.email
FROM payments p
JOIN users u ON p.user_id = u.id
";

if ($where) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY CASE WHEN p.status='pending' OR p.status='' OR p.status IS NULL THEN 0 ELSE 1 END, p.id DESC";
$payments = $mysqli->query($query);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
body{background:linear-gradient(135deg,#0f172a,#1e293b);color:#fff;min-height:100vh;padding:32px 18px;}
.page{max-width:1320px;margin:0 auto;}
.topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:22px;}
.back-link,.action-link{display:inline-flex;padding:10px 18px;border-radius:999px;text-decoration:none;color:#fff;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.08);}
.hero,.panel,.stat-card{background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);border-radius:24px;box-shadow:0 20px 40px rgba(0,0,0,0.18);}
.hero{padding:22px 24px;margin-bottom:18px;}
.hero p{color:#cbd5e1;line-height:1.6;margin-top:8px;max-width:780px;}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:18px;}
.stat-card{padding:18px 20px;}
.stat-card span{display:block;color:#cbd5e1;font-size:14px;margin-bottom:8px;}
.stat-card strong{font-size:28px;}
.panel{padding:20px;}
.filters{display:grid;grid-template-columns:1fr 180px auto;gap:12px;align-items:end;margin-bottom:18px;}
.field label{display:block;font-size:13px;color:#cbd5e1;margin-bottom:6px;}
.field input,.field select{width:100%;padding:12px 14px;border-radius:14px;border:1px solid rgba(255,255,255,0.08);background:rgba(15,23,42,0.6);color:#fff;outline:none;}
.submit-btn,.approve-btn,.reject-btn{border:none;border-radius:14px;padding:12px 16px;color:#fff;cursor:pointer;font-weight:600;}
.submit-btn{background:linear-gradient(90deg,#2563eb,#4f46e5);}
.approve-btn{background:#16a34a;}
.reject-btn{background:#dc2626;}
.message{padding:14px 16px;border-radius:16px;margin-bottom:16px;}
.message.success{background:rgba(22,163,74,0.2);border:1px solid rgba(74,222,128,0.35);}
.message.error{background:rgba(220,38,38,0.2);border:1px solid rgba(248,113,113,0.35);}
.table-wrap{overflow:auto;border-radius:18px;}
table{width:100%;border-collapse:collapse;min-width:1100px;}
th,td{padding:14px 12px;text-align:left;vertical-align:top;}
th{color:#cbd5e1;font-size:13px;border-bottom:1px solid rgba(255,255,255,0.08);}
td{border-bottom:1px solid rgba(255,255,255,0.06);}
.status-chip{display:inline-flex;padding:6px 10px;border-radius:999px;font-size:12px;text-transform:capitalize;}
.status-pending{background:rgba(245,158,11,0.18);color:#fcd34d;}
.status-success{background:rgba(22,163,74,0.18);color:#86efac;}
.status-rejected{background:rgba(220,38,38,0.18);color:#fca5a5;}
.thumb{width:64px;height:64px;object-fit:cover;border-radius:10px;border:1px solid rgba(255,255,255,0.08);}
.stack{display:grid;gap:10px;}
.inline-form{display:grid;gap:10px;}
.inline-form select{padding:10px 12px;border-radius:12px;border:1px solid rgba(255,255,255,0.08);background:rgba(15,23,42,0.6);color:#fff;}
.inline-actions{display:flex;gap:8px;flex-wrap:wrap;}
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
        <h1>Payments Review</h1>
        <p>Approve pending payments by selecting the correct plan directly in the table. This keeps the review flow simple and ensures subscriptions are created with the seat type the user system expects.</p>
    </div>

    <div class="stats">
        <div class="stat-card"><span>Pending</span><strong><?= intval($summary['pending_count'] ?? 0) ?></strong></div>
        <div class="stat-card"><span>Approved</span><strong><?= intval($summary['approved_count'] ?? 0) ?></strong></div>
        <div class="stat-card"><span>Rejected</span><strong><?= intval($summary['rejected_count'] ?? 0) ?></strong></div>
    </div>

    <div class="panel">
        <?php if ($message !== ""): ?>
            <div class="message <?= $messageType === 'error' ? 'error' : 'success' ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="GET" class="filters">
            <div class="field">
                <label>Search by name, email, transaction, or UTR</label>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search payments">
            </div>
            <div class="field">
                <label>Status</label>
                <select name="status">
                    <option value="all" <?= $selectedStatus === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="pending" <?= $selectedStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="success" <?= $selectedStatus === 'success' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= $selectedStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <button class="submit-btn" type="submit">Apply Filters</button>
        </form>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Reference</th>
                    <th>Screenshot</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
                <?php while ($row = $payments->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div><?= htmlspecialchars($row['name']) ?></div>
                            <div class="muted"><?= htmlspecialchars($row['email']) ?></div>
                        </td>
                        <td>Rs <?= number_format((float)$row['amount'], 2) ?></td>
                        <td>
                            <div><?= htmlspecialchars($row['transaction_id'] ?: '-') ?></div>
                            <div class="muted">UTR: <?= htmlspecialchars($row['utr_no'] ?: '-') ?></div>
                        </td>
                        <td>
                            <?php if (!empty($row['screenshot_path'])): ?>
                                <a href="../<?= htmlspecialchars($row['screenshot_path']) ?>" target="_blank" rel="noreferrer">
                                    <img class="thumb" src="../<?= htmlspecialchars($row['screenshot_path']) ?>" alt="Payment screenshot">
                                </a>
                            <?php else: ?>
                                <span class="muted">No screenshot</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="status-chip status-<?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                        <td>
                            <?php if ($row['status'] === 'pending'): ?>
                                <div class="stack">
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="payment_id" value="<?= intval($row['id']) ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="amount" value="<?= htmlspecialchars($row['amount']) ?>">
                                        <?php $defaultKey = get_default_plan_key_for_amount((float)$row['amount']); ?>
                                        <select name="plan_key">
                                            <?php foreach ($planOptions as $key => $plan): ?>
                                                <option value="<?= htmlspecialchars($key) ?>" <?= $defaultKey === $key ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($plan['label']) ?> | Rs <?= number_format((float)$plan['price'], 0) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="approve-btn" type="submit">Approve</button>
                                    </form>
                                    <form method="POST" class="inline-actions" onsubmit="return confirm('Reject this payment?');">
                                        <input type="hidden" name="payment_id" value="<?= intval($row['id']) ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button class="reject-btn" type="submit">Reject</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="muted">Completed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>
