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

$historyStmt = $mysqli->prepare("
    SELECT entry_date, updated_at, LEFT(content, 90) AS preview
    FROM diary_entries
    WHERE user_id=?
      AND content <> ''
    ORDER BY entry_date DESC
    LIMIT 60
");
$historyStmt->bind_param("i", $user_id);
$historyStmt->execute();
$historyRows = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);

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


.diary-history{margin-top:22px;padding-top:18px;border-top:1px solid rgba(255,255,255,.10);}
.history-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px;}
.history-head h3{font-size:17px;margin:0;color:#f7fbff;}
.history-head span{font-size:11px;color:#9fb1c6;}
.history-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px;max-height:235px;overflow:auto;padding-right:3px;}
.history-item{display:flex;flex-direction:column;align-items:flex-start;text-align:left;gap:4px;padding:11px 13px!important;border-radius:13px!important;background:rgba(255,255,255,.045)!important;border:1px solid rgba(165,205,245,.15)!important;box-shadow:none!important;cursor:pointer;}
.history-item:hover{background:rgba(255,255,255,.08)!important;border-color:rgba(139,92,246,.40)!important;}
.history-date{font-size:12px;font-weight:600;color:#e9f2ff;}
.history-preview{font-size:11px;line-height:1.35;color:#9fb1c6;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.history-empty{padding:14px;border:1px dashed rgba(165,205,245,.18);border-radius:13px;color:#9fb1c6;font-size:12px;}
@media(max-width:700px){.history-list{grid-template-columns:1fr;}}

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
    
<div class="diary-history">
    <div class="history-head">
        <h3>Previous Entries</h3>
        <span>Saved diary entries</span>
    </div>
    <div class="history-list">
        <?php if (!$historyRows): ?>
            <div class="history-empty">No previous diary entries found.</div>
        <?php else: ?>
            <?php foreach ($historyRows as $row): ?>
                <button type="button" class="history-item" data-date="<?= htmlspecialchars($row['entry_date']) ?>">
                    <span class="history-date"><?= htmlspecialchars(date("d M Y", strtotime($row['entry_date']))) ?></span>
                    <span class="history-preview"><?= htmlspecialchars($row['preview']) ?></span>
                </button>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</div>
</div>

<script>
const csrfToken = "<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>";
let diaryDate = "<?= $entry_date ?>";
const saveBtn = document.getElementById("saveDiaryBtn");
const diaryContent = document.getElementById("diaryContent");
const statusText = document.getElementById("statusText");
const updatedAt = document.getElementById("updatedAt");


function loadDiaryDate(date) {
    statusText.textContent = "Loading...";
    fetch("api/get-diary-entry.php?date=" + encodeURIComponent(date))
        .then(res => res.json())
        .then(data => {
            diaryDate = data.date;
            diaryContent.value = data.content || "";
            updatedAt.textContent = data.updated_at || "Not saved yet";
            statusText.textContent = "";
            document.querySelectorAll(".history-item").forEach(btn => {
                btn.classList.toggle("selected", btn.dataset.date === diaryDate);
            });
        })
        .catch(() => {
            statusText.textContent = "Unable to load this entry.";
        });
}
document.querySelectorAll(".history-item").forEach(btn => {
    btn.addEventListener("click", () => loadDiaryDate(btn.dataset.date));
});

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
