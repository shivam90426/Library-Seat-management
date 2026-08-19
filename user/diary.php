<?php
require_once "../includes/security.php";
library_system_bootstrap();
require_once "../config/db.php";
require_once "../includes/diary_helpers.php";

require_login('user');

ensure_diary_entries_table($mysqli);

$user_id = intval($_SESSION['user_id']);
$entry_date = date("Y-m-d");

$stmt = $mysqli->prepare("
SELECT content, updated_at
FROM diary_entries
WHERE user_id=? AND entry_date=?
LIMIT 1
");
$stmt->bind_param("is", $user_id, $entry_date);
$stmt->execute();
$entry = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Diary</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
body{
min-height:100vh;
background:linear-gradient(120deg,#f8fafc,#e0f2fe);
padding:32px 18px;
color:#1e293b;
}
.page{
max-width:960px;
margin:0 auto;
}
.back-link{
display:inline-flex;
margin-bottom:22px;
padding:10px 18px;
border-radius:999px;
background:#fff;
color:#2563eb;
text-decoration:none;
box-shadow:0 12px 25px rgba(37,99,235,0.12);
}
.card{
background:#fff;
border-radius:28px;
padding:32px;
box-shadow:0 25px 50px rgba(15,23,42,0.1);
}
.card h1{
margin-bottom:10px;
}
.meta{
display:flex;
justify-content:space-between;
gap:14px;
flex-wrap:wrap;
margin-bottom:20px;
font-size:14px;
color:#64748b;
}
textarea{
width:100%;
min-height:340px;
border:none;
outline:none;
resize:vertical;
padding:22px;
border-radius:22px;
background:#f8fafc;
line-height:1.7;
font-size:15px;
color:#1e293b;
}
.actions{
display:flex;
justify-content:space-between;
align-items:center;
gap:16px;
flex-wrap:wrap;
margin-top:18px;
}
button{
border:none;
padding:12px 22px;
border-radius:999px;
background:linear-gradient(90deg,#2563eb,#4f46e5);
color:#fff;
font-weight:600;
cursor:pointer;
}
#statusText{
color:#64748b;
font-size:14px;
}
</style>
</head>
<body>
<div class="page">
    <a class="back-link" href="dashboard.php">Back to Dashboard</a>

    <div class="card">
        <h1>Today’s Diary</h1>

        <div class="meta">
            <div>Date: <?= htmlspecialchars($entry_date) ?></div>
            <div>Last update: <span id="updatedAt"><?= htmlspecialchars($entry['updated_at'] ?? 'Not saved yet') ?></span></div>
        </div>

        <textarea id="diaryContent" placeholder="Write down what you studied, what you want to remember, or how the day went."><?= htmlspecialchars($entry['content'] ?? '') ?></textarea>

        <div class="actions">
            <button type="button" id="saveDiaryBtn">Save Entry</button>
            <span id="statusText"></span>
        </div>
    </div>
</div>

<script>
const csrfToken = "<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>";
const diaryDate = "<?= $entry_date ?>";
const saveBtn = document.getElementById("saveDiaryBtn");
const diaryContent = document.getElementById("diaryContent");
const statusText = document.getElementById("statusText");
const updatedAt = document.getElementById("updatedAt");

saveBtn.addEventListener("click", function () {
    statusText.textContent = "Saving...";

    fetch("api/save-diary-entry.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-CSRF-Token": csrfToken
        },
        body: "date=" + encodeURIComponent(diaryDate) + "&content=" + encodeURIComponent(diaryContent.value)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status !== "success") {
            statusText.textContent = data.message || "Unable to save right now.";
            return;
        }

        statusText.textContent = "Saved successfully.";
        updatedAt.textContent = data.updated_at;
    })
    .catch(() => {
        statusText.textContent = "Unable to save right now.";
    });
});
</script>
</body>
</html>
